<?php
require_once __DIR__ . '/common.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare('SELECT id, name, email, role, company_name, services_offered, past_experience, created_at FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    jsonResponse(['user' => $stmt->fetch()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = getJsonInput();
    $companyName = sanitize($input['company_name'] ?? '');
    $services = sanitize($input['services'] ?? '');
    $experience = sanitize($input['past_experience'] ?? '');
    $stmt = $pdo->prepare('UPDATE users SET company_name = ?, services_offered = ?, past_experience = ? WHERE id = ?');
    $stmt->execute([$companyName, $services, $experience, $_SESSION['user_id']]);
    $_SESSION['user'] = getUserById($_SESSION['user_id']);
    jsonResponse(['message' => 'Profile updated successfully.']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
