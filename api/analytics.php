<?php
require_once __DIR__ . '/common.php';

requireAuth();

if ($_SESSION['user']['role'] !== 'admin') {
    requireSupplier();
}

$overview = [];
if ($_SESSION['user']['role'] === 'admin') {
    $stmt = $pdo->query('SELECT COUNT(*) AS open_procurements FROM procurements WHERE status = "open"');
    $overview['open_procurements'] = (int)$stmt->fetchColumn();
    $stmt = $pdo->query('SELECT COUNT(*) AS suppliers FROM users WHERE role = "supplier"');
    $overview['suppliers'] = (int)$stmt->fetchColumn();
    $stmt = $pdo->query('SELECT COUNT(*) AS active_bids FROM bids WHERE status = "pending"');
    $overview['active_bids'] = (int)$stmt->fetchColumn();
    $stmt = $pdo->query('SELECT AVG(price) AS avg_price FROM bids');
    $overview['avg_price'] = (float)($stmt->fetchColumn() ?: 0);
} else {
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total_bids, SUM(CASE WHEN status = "awarded" THEN 1 ELSE 0 END) AS wins FROM bids WHERE supplier_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch();
    $overview['total_bids'] = (int)$stats['total_bids'];
    $overview['wins'] = (int)$stats['wins'];
    $overview['win_rate'] = $stats['total_bids'] ? intval($stats['wins'] / $stats['total_bids'] * 100) : 0;
    $overview['reliability'] = computeReliabilityScore($_SESSION['user_id']);
}

$stmt = $pdo->query('SELECT status, COUNT(*) AS total FROM procurements GROUP BY status');
$statusCounts = ['open' => 0, 'closed' => 0];
foreach ($stmt->fetchAll() as $row) {
    $statusCounts[$row['status']] = (int)$row['total'];
}

jsonResponse(['overview' => $overview, 'status_counts' => $statusCounts]);
