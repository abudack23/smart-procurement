<?php
require_once __DIR__ . '/common.php';

requireAdmin();

$stmt = $pdo->query('SELECT id, name, email, role, company_name, services_offered, past_experience, created_at FROM users ORDER BY created_at DESC');
jsonResponse(['users' => $stmt->fetchAll()]);
