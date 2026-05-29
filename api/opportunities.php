<?php
require_once __DIR__ . '/common.php';

requireSupplier();

$q = sanitize($_GET['q'] ?? '');
$minBudget = floatval($_GET['min_budget'] ?? 0);
$maxBudget = floatval($_GET['max_budget'] ?? 0);
$deadline = sanitize($_GET['deadline'] ?? '');

$where = ['p.status = "open"'];
$params = [];
if ($q !== '') {
    $where[] = '(p.title LIKE ? OR p.description LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($minBudget > 0) {
    $where[] = 'CAST(REPLACE(p.budget, ",", "") AS DECIMAL(12,2)) >= ?';
    $params[] = $minBudget;
}
if ($maxBudget > 0) {
    $where[] = 'CAST(REPLACE(p.budget, ",", "") AS DECIMAL(12,2)) <= ?';
    $params[] = $maxBudget;
}
if ($deadline !== '') {
    $where[] = 'p.submission_deadline <= ?';
    $params[] = $deadline;
}

$sql = 'SELECT p.*, COUNT(b.id) AS bids FROM procurements p LEFT JOIN bids b ON p.id = b.procurement_id';
$sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' GROUP BY p.id ORDER BY p.submission_deadline ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
jsonResponse(['opportunities' => $stmt->fetchAll()]);
