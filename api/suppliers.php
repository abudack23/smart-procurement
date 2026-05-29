<?php
require_once __DIR__ . '/common.php';

requireAdmin();

$stmt = $pdo->query('SELECT id, name, email, company_name, services_offered, past_experience, created_at FROM users WHERE role = "supplier" ORDER BY created_at DESC');
jsonResponse(['suppliers' => $stmt->fetchAll()]);
