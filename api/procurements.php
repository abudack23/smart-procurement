<?php
require_once __DIR__ . '/common.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM procurements ORDER BY created_at DESC');
    jsonResponse(['procurements' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $input = getJsonInput();
        $title = sanitize($input['title'] ?? '');
        $description = sanitize($input['description'] ?? '');
        $budget = sanitize($input['budget'] ?? '');
        $deliveryDays = intval($input['delivery_days'] ?? 0);
        $deadline = sanitize($input['submission_deadline'] ?? '');
        $evaluationCriteria = sanitize($input['evaluation_criteria'] ?? 'Price,Delivery time,Reliability');
        $supportDocument = null;
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $budget = sanitize($_POST['budget'] ?? '');
        $deliveryDays = intval($_POST['delivery_days'] ?? 0);
        $deadline = sanitize($_POST['submission_deadline'] ?? '');
        $evaluationCriteria = sanitize($_POST['evaluation_criteria'] ?? 'Price,Delivery time,Reliability');
        $supportDocument = uploadFile($_FILES['support_document'] ?? null);
    }

    if (!$title || !$description || !$deadline || $deliveryDays <= 0) {
        jsonResponse(['error' => 'Please provide title, description, deadline, and delivery days.'], 422);
    }
    $stmt = $pdo->prepare('INSERT INTO procurements (title, description, budget, delivery_days, submission_deadline, evaluation_criteria, support_document, created_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), "open")');
    $stmt->execute([$title, $description, $budget, $deliveryDays, $deadline, $evaluationCriteria, $supportDocument]);
    $procurementId = $pdo->lastInsertId();
    logAction($_SESSION['user_id'], 'Created procurement', $title);
    notifyAllSuppliers('New Procurement Opportunity', "A new procurement request has been posted: $title\nDeadline: $deadline\nBudget: " . ($budget ?: 'N/A') . "\nPlease review the opportunity and submit your bid.");
    jsonResponse(['message' => 'Procurement request created successfully.', 'procurement_id' => $procurementId]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
