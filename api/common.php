<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$allowedOrigins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost',
    'http://127.0.0.1'
];
if (!empty($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
    exit;
}

function jsonResponse($data, int $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function requireAuth() {
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
}

function requireAdmin() {
    $role = $_SESSION['user']['role'] ?? '';
    if (!isLoggedIn() || !in_array($role, ['admin', 'superadmin'], true)) {
        jsonResponse(['error' => 'Admin access required'], 403);
    }
}

function requireSupplier() {
    if (!isLoggedIn() || ($_SESSION['user']['role'] ?? '') !== 'supplier') {
        jsonResponse(['error' => 'Supplier access required'], 403);
    }
}

function getJsonInput() {
    $body = file_get_contents('php://input');
    if (!$body) {
        return [];
    }
    return json_decode($body, true) ?? [];
}
?>