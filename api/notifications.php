<?php
require_once __DIR__ . '/common.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$_SESSION['user_id']]);
    jsonResponse(['notifications' => $stmt->fetchAll()]);
}

$input = getJsonInput();
action:
$action = $input['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'mark_read') {
        $notificationId = intval($input['notification_id'] ?? 0);
        if (!$notificationId) {
            jsonResponse(['error' => 'Notification ID is required.'], 422);
        }
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        $stmt->execute([$notificationId, $_SESSION['user_id']]);
        jsonResponse(['message' => 'Notification marked read.']);
    }
    if ($action === 'mark_all_read') {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$_SESSION['user_id']]);
        jsonResponse(['message' => 'All notifications marked read.']);
    }
}

jsonResponse(['error' => 'Method not allowed'], 405);
