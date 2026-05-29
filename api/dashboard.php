<?php
require_once __DIR__ . '/common.php';

requireAuth();

$user = currentUser();
$response = ['user' => $user];

if ($user['role'] === 'admin') {
    $response['overview'] = [
        'open_procurements' => countProcurements(),
        'active_bids' => countOngoingBids(),
        'completed_bids' => countCompletedBids(),
        'suppliers' => countBidders()
    ];

    $statusCounts = ['open' => 0, 'closed' => 0];
    $stmt = $pdo->query('SELECT status, COUNT(*) AS total FROM procurements GROUP BY status');
    foreach ($stmt->fetchAll() as $row) {
        $statusCounts[$row['status']] = (int)$row['total'];
    }
    $response['status_counts'] = $statusCounts;
    $stmt = $pdo->query('SELECT DATE_FORMAT(created_at, "%b %Y") AS month_label, COUNT(*) AS total FROM procurements WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY month_label ORDER BY MIN(created_at) ASC');
    $trend = ['labels' => [], 'values' => []];
    foreach ($stmt->fetchAll() as $row) {
        $trend['labels'][] = $row['month_label'];
        $trend['values'][] = (int)$row['total'];
    }
    $response['trend'] = $trend;
    $stmt = $pdo->query('SELECT id, title, budget, submission_deadline, status FROM procurements ORDER BY created_at DESC LIMIT 5');
    $response['recent_procurements'] = $stmt->fetchAll();
} else {
    $stats = getUserStats($_SESSION['user_id']);
    $response['overview'] = [
        'total_bids' => (int)$stats['total_bids'],
        'wins' => (int)$stats['wins'],
        'win_rate' => $stats['total_bids'] ? intval($stats['wins'] / $stats['total_bids'] * 100) : 0,
        'reliability' => computeReliabilityScore($_SESSION['user_id'])
    ];
    $stmt = $pdo->prepare('SELECT status, COUNT(*) AS total FROM bids WHERE supplier_id = ? GROUP BY status');
    $stmt->execute([$_SESSION['user_id']]);
    $status = ['pending' => 0, 'awarded' => 0, 'rejected' => 0];
    foreach ($stmt->fetchAll() as $row) {
        $status[$row['status']] = (int)$row['total'];
    }
    $response['bid_status'] = $status;
    $response['recent_bids'] = [];
    $stmt = $pdo->prepare('SELECT b.id, p.title, b.price, b.delivery_days, b.status, b.created_at FROM bids b JOIN procurements p ON b.procurement_id = p.id WHERE b.supplier_id = ? ORDER BY b.created_at DESC LIMIT 5');
    $stmt->execute([$_SESSION['user_id']]);
    $response['recent_bids'] = $stmt->fetchAll();
}

jsonResponse($response);
