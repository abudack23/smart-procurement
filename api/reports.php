<?php
require_once __DIR__ . '/common.php';

requireAdmin();

$stmt = $pdo->query('SELECT p.title, COUNT(b.id) AS bids_count, AVG(b.price) AS avg_price, SUM(CASE WHEN b.status = "awarded" THEN 1 ELSE 0 END) AS awarded_count FROM procurements p LEFT JOIN bids b ON p.id = b.procurement_id GROUP BY p.id ORDER BY p.created_at DESC');
$procurements = $stmt->fetchAll();
$stmt = $pdo->query('SELECT u.name, u.company_name, COUNT(b.id) AS total_bids, SUM(CASE WHEN b.status = "awarded" THEN 1 ELSE 0 END) AS wins FROM users u LEFT JOIN bids b ON u.id = b.supplier_id WHERE u.role = "supplier" GROUP BY u.id ORDER BY wins DESC');
$suppliers = $stmt->fetchAll();
$stmt = $pdo->query('SELECT a.*, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 50');
$audit = $stmt->fetchAll();
jsonResponse(['procurements' => $procurements, 'suppliers' => $suppliers, 'audit' => $audit]);
