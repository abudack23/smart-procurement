<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/ai.php';
require_once __DIR__ . '/common.php';

header('Content-Type: application/json');

// Require admin role
$user = currentUser();
if (!isLoggedIn() || !in_array($user['role'], ['admin', 'superadmin'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;

if ($action === 'get_procurement_bids') {
    $procurementId = $_GET['procurement_id'] ?? null;
    
    if (!$procurementId) {
        http_response_code(400);
        echo json_encode(['error' => 'procurement_id required']);
        exit;
    }
    
    $rankedBids = getRankedBidsForProcurement($procurementId);
    echo json_encode(['success' => true, 'bids' => $rankedBids]);
    exit;
}

if ($action === 'get_procurement_list') {
    global $pdo;
    $stmt = $pdo->query('SELECT id, title, budget, status, created_at FROM procurements ORDER BY created_at DESC LIMIT 50');
    $procurements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'procurements' => $procurements]);
    exit;
}

if ($action === 'get_bid_evaluation') {
    $bidId = $_GET['bid_id'] ?? null;
    $procurementId = $_GET['procurement_id'] ?? null;
    
    if (!$bidId || !$procurementId) {
        http_response_code(400);
        echo json_encode(['error' => 'bid_id and procurement_id required']);
        exit;
    }
    
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM bids WHERE id = ? AND procurement_id = ? LIMIT 1');
    $stmt->execute([$bidId, $procurementId]);
    $bid = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bid) {
        http_response_code(404);
        echo json_encode(['error' => 'Bid not found']);
        exit;
    }
    
    $procurement = getProcurementById($procurementId);
    $evaluation = evaluateBid($bid, $procurement);
    
    // Get supplier info
    $supplier = getUserById($bid['supplier_id']);
    $performanceMetrics = getSupplierPerformanceMetrics($bid['supplier_id']);
    
    echo json_encode([
        'success' => true,
        'bid' => $bid,
        'supplier' => [
            'id' => $supplier['id'],
            'name' => $supplier['name'],
            'email' => $supplier['email']
        ],
        'evaluation' => $evaluation,
        'performance' => $performanceMetrics
    ]);
    exit;
}

if ($action === 'get_ai_recommendations') {
    $procurementId = $_GET['procurement_id'] ?? null;
    
    if (!$procurementId) {
        http_response_code(400);
        echo json_encode(['error' => 'procurement_id required']);
        exit;
    }
    
    $rankedBids = getRankedBidsForProcurement($procurementId);
    
    if (empty($rankedBids)) {
        echo json_encode(['success' => true, 'recommendations' => [], 'message' => 'No bids found']);
        exit;
    }
    
    $recommendations = [
        'best_overall' => $rankedBids[0],
        'lowest_cost' => array_reduce($rankedBids, function($carry, $bid) {
            return $carry === null || $bid['price'] < $carry['price'] ? $bid : $carry;
        }, null),
        'fastest_delivery' => array_reduce($rankedBids, function($carry, $bid) {
            return $carry === null || $bid['delivery_days'] < $carry['delivery_days'] ? $bid : $carry;
        }, null),
        'most_reliable' => array_reduce($rankedBids, function($carry, $bid) {
            return $carry === null || $bid['reliability_score'] > $carry['reliability_score'] ? $bid : $carry;
        }, null),
    ];
    
    echo json_encode(['success' => true, 'recommendations' => $recommendations]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
