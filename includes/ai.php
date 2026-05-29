<?php
require_once __DIR__ . '/config.php';

function getTrainingSamples() {
    static $samples = null;
    if ($samples === null) {
        $samples = require __DIR__ . '/training_data.php';
    }
    return $samples;
}

function computeTextMatchWeight($text, $keywords) {
    $score = 0;
    foreach ($keywords as $keyword) {
        if (stripos($text, $keyword) !== false) {
            $score += 1;
        }
    }
    return $score;
}

function predictPriceRangeFromTraining($procurement) {
    $samples = getTrainingSamples();
    $text = strtolower($procurement['title'] . ' ' . $procurement['description']);
    $budget = floatval($procurement['budget'] ?? 0);
    $totalWeight = 0;
    $weightedSum = 0;
    $low = null;
    $high = null;

    foreach ($samples as $sample) {
        $weight = computeTextMatchWeight($text, $sample['keywords']);
        if ($budget > 0 && $sample['budget'] > 0) {
            $budgetDistance = abs($budget - $sample['budget']) / max(1, $sample['budget']);
            if ($budgetDistance < 0.35) {
                $weight += 2;
            }
        }
        if ($weight <= 0) {
            continue;
        }
        $weightedSum += $sample['avg_price'] * $weight;
        $totalWeight += $weight;
        $low = $low === null ? $sample['avg_price'] * 0.7 : min($low, $sample['avg_price'] * 0.7);
        $high = $high === null ? $sample['avg_price'] * 1.3 : max($high, $sample['avg_price'] * 1.3);
    }

    if ($totalWeight === 0) {
        $avg = $budget > 0 ? max(0, $budget * 0.9) : 0;
        return ['low' => intval($avg * 0.75), 'avg' => intval($avg), 'high' => intval($avg * 1.2)];
    }

    $avg = intval($weightedSum / $totalWeight);
    return ['low' => intval($low ?: $avg * 0.75), 'avg' => $avg, 'high' => intval($high ?: $avg * 1.2)];
}

function predictDelayRateFromTraining($procurement) {
    $samples = getTrainingSamples();
    $text = strtolower($procurement['title'] . ' ' . $procurement['description']);
    $budget = floatval($procurement['budget'] ?? 0);
    $totalWeight = 0;
    $weightedRisk = 0;

    foreach ($samples as $sample) {
        $weight = computeTextMatchWeight($text, $sample['keywords']);
        if ($budget > 0 && $sample['budget'] > 0) {
            $budgetDistance = abs($budget - $sample['budget']) / max(1, $sample['budget']);
            if ($budgetDistance < 0.35) {
                $weight += 1;
            }
        }
        if ($weight <= 0) {
            continue;
        }
        $weightedRisk += $sample['delay_rate'] * $weight;
        $totalWeight += $weight;
    }

    if ($totalWeight === 0) {
        return 25;
    }

    return intval($weightedRisk / $totalWeight);
}

function computePriceScore($bidPrice, $average, $minPrice, $maxPrice) {
    if ($bidPrice <= $minPrice) {
        return 95;
    }
    if ($bidPrice <= $average) {
        return 70 + intval((($average - $bidPrice) / max(1, $average)) * 30);
    }
    if ($bidPrice <= $maxPrice) {
        return max(20, 50 - intval((($bidPrice - $average) / max(1, $average)) * 40));
    }
    return 20;
}

function computeDeliveryScore($deliveryDays, $targetDays) {
    if ($deliveryDays <= $targetDays) {
        return 90;
    }
    $over = $deliveryDays - $targetDays;
    return max(10, 90 - $over * 5);
}

function computeReliabilityScore($supplierId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total, SUM(CASE WHEN status = "awarded" THEN 1 ELSE 0 END) AS wins, SUM(CASE WHEN delivery_status = "on-time" THEN 1 ELSE 0 END) AS ontime FROM bids WHERE supplier_id = ?');
    $stmt->execute([(int)$supplierId]);
    $stats = $stmt->fetch();
    if (!$stats || (int)$stats['total'] === 0) {
        return 0;
    }
    $winRate = $stats['wins'] / max(1, $stats['total']);
    $onTimeRate = $stats['ontime'] / max(1, $stats['total']);
    return min(100, intval(40 * $winRate + 60 * $onTimeRate + 30));
}

function rateDelayRisk($deliveryDays, $targetDays, $supplierId, $procurement) {
    $baseScore = computeDeliveryScore($deliveryDays, $targetDays);
    $supplierReliability = computeReliabilityScore($supplierId);
    $trainingRisk = predictDelayRateFromTraining($procurement);
    $risk = 100 - intval(($baseScore * 0.55) + ($supplierReliability * 0.3) + ((100 - $trainingRisk) * 0.15));
    return max(10, min(100, $risk));
}

function evaluateBid($bid, $procurement) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT AVG(price) AS avg_price, MIN(price) AS min_price, MAX(price) AS max_price FROM bids WHERE procurement_id = ?');
    $stmt->execute([$procurement['id']]);
    $stats = $stmt->fetch();
    $avg = $stats['avg_price'] ?: $bid['price'];
    $min = $stats['min_price'] ?: $bid['price'];
    $max = $stats['max_price'] ?: $bid['price'];

    $priceScore = computePriceScore($bid['price'], $avg, $min, $max);
    $deliveryScore = computeDeliveryScore($bid['delivery_days'], $procurement['delivery_days']);
    $reliabilityScore = computeReliabilityScore($bid['supplier_id']);
    $finalScore = intval($priceScore * 0.5 + $deliveryScore * 0.3 + $reliabilityScore * 0.2);
    $rangeLabel = 'Within expected range';
    if ($bid['price'] > $avg * 1.15) {
        $rangeLabel = 'Too high';
    } elseif ($bid['price'] < $avg * 0.75) {
        $rangeLabel = 'Suspiciously low';
    }
    $trainingEstimate = predictPriceRangeFromTraining($procurement);
    $bidBelowTraining = $bid['price'] < $trainingEstimate['low'];
    $bidAboveTraining = $bid['price'] > $trainingEstimate['high'];

    return [
        'price_score' => $priceScore,
        'delivery_score' => $deliveryScore,
        'reliability_score' => $reliabilityScore,
        'final_score' => $finalScore,
        'price_label' => $rangeLabel,
        'delay_risk' => rateDelayRisk($bid['delivery_days'], $procurement['delivery_days'], $bid['supplier_id'], $procurement),
        'avg_price' => $avg,
        'min_price' => $min,
        'max_price' => $max,
        'training_price_low' => $trainingEstimate['low'],
        'training_price_avg' => $trainingEstimate['avg'],
        'training_price_high' => $trainingEstimate['high'],
        'training_price_note' => $bidBelowTraining ? 'Bid is lower than expected training range' : ($bidAboveTraining ? 'Bid is above expected training range' : 'Bid is aligned with predicted range'),
    ];
}

function estimatePriceRange($procurementId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT AVG(price) AS avg_price, MIN(price) AS min_price, MAX(price) AS max_price FROM bids WHERE procurement_id = ?');
    $stmt->execute([$procurementId]);
    $stats = $stmt->fetch();
    if ($stats['avg_price']) {
        return $stats;
    }
    $stmt = $pdo->prepare('SELECT * FROM procurements WHERE id = ?');
    $stmt->execute([$procurementId]);
    $procurement = $stmt->fetch();
    if (!$procurement) {
        return ['avg_price' => 0, 'min_price' => 0, 'max_price' => 0];
    }
    $training = predictPriceRangeFromTraining($procurement);
    return ['avg_price' => $training['avg'], 'min_price' => $training['low'], 'max_price' => $training['high']];
}

function getSupplierTrend($supplierId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT delivery_status, status, created_at FROM bids WHERE supplier_id = ? ORDER BY created_at DESC LIMIT 6');
    $stmt->execute([$supplierId]);
    $rows = $stmt->fetchAll();
    $trend = ['labels' => [], 'on_time' => [], 'awarded' => [], 'rejected' => []];
    foreach (array_reverse($rows) as $row) {
        $trend['labels'][] = date('M j', strtotime($row['created_at']));
        $trend['on_time'][] = $row['delivery_status'] === 'on-time' ? 1 : 0;
        $trend['awarded'][] = $row['status'] === 'awarded' ? 1 : 0;
        $trend['rejected'][] = $row['status'] === 'rejected' ? 1 : 0;
    }
    return $trend;
}
