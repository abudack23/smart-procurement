<?php
require_once __DIR__ . '/common.php';

$method = $_SERVER['REQUEST_METHOD'];
$isJson = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
$input = $isJson ? getJsonInput() : $_POST;

if ($method === 'GET') {
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
    $procurementId = intval($_GET['procurement_id'] ?? 0);
    if ($_SESSION['user']['role'] === 'admin') {
        if ($procurementId) {
            $stmt = $pdo->prepare('SELECT b.*, u.name AS supplier_name, p.title FROM bids b JOIN users u ON b.supplier_id = u.id JOIN procurements p ON b.procurement_id = p.id WHERE procurement_id = ? ORDER BY b.created_at DESC');
            $stmt->execute([$procurementId]);
            jsonResponse(['bids' => $stmt->fetchAll()]);
        }
        $stmt = $pdo->query('SELECT b.*, u.name AS supplier_name, p.title FROM bids b JOIN users u ON b.supplier_id = u.id JOIN procurements p ON b.procurement_id = p.id ORDER BY b.created_at DESC');
        jsonResponse(['bids' => $stmt->fetchAll()]);
    }
    if ($_SESSION['user']['role'] === 'supplier') {
        $stmt = $pdo->prepare('SELECT b.*, p.title FROM bids b JOIN procurements p ON b.procurement_id = p.id WHERE supplier_id = ? ORDER BY b.created_at DESC');
        $stmt->execute([$_SESSION['user_id']]);
        jsonResponse(['bids' => $stmt->fetchAll()]);
    }
}

if ($method === 'POST') {
    $action = $input['action'] ?? '';
    if ($action === 'award') {
        requireAdmin();
        $bidId = intval($input['bid_id'] ?? 0);
        $procurementId = intval($input['procurement_id'] ?? 0);
        if (!$bidId || !$procurementId) {
            jsonResponse(['error' => 'Bid ID and procurement ID are required.'], 422);
        }
        $stmt = $pdo->prepare('SELECT * FROM procurements WHERE id = ?');
        $stmt->execute([$procurementId]);
        $procurement = $stmt->fetch();
        if (!$procurement) {
            jsonResponse(['error' => 'Procurement not found.'], 404);
        }
        $stmt = $pdo->prepare('UPDATE bids SET status = "awarded" WHERE id = ?');
        $stmt->execute([$bidId]);
        $stmt = $pdo->prepare('UPDATE procurements SET status = "closed" WHERE id = ?');
        $stmt->execute([$procurementId]);
        $winning = $pdo->prepare('SELECT supplier_id FROM bids WHERE id = ?');
        $winning->execute([$bidId]);
        $winner = $winning->fetch();
        $stmt = $pdo->prepare('UPDATE bids SET status = "rejected" WHERE procurement_id = ? AND id != ?');
        $stmt->execute([$procurementId, $bidId]);
        if ($winner && $winner['supplier_id']) {
            notifySupplier($winner['supplier_id'], 'Bid Awarded', "Congratulations! Your bid for '{$procurement['title']}' has been awarded.");
        }
        $losers = $pdo->prepare('SELECT supplier_id FROM bids WHERE procurement_id = ? AND id != ?');
        $losers->execute([$procurementId, $bidId]);
        while ($row = $losers->fetch()) {
            notifySupplier($row['supplier_id'], 'Bid Result', "Your bid for '{$procurement['title']}' was not selected. Thank you for participating.");
        }
        logAction($_SESSION['user_id'], 'Awarded bid', 'Bid ID: ' . $bidId . ' Procurement ID: ' . $procurementId);
        jsonResponse(['message' => 'Bid awarded successfully.']);
    }
    if ($action === 'submit') {
        requireSupplier();
        $procurementId = intval($input['procurement_id'] ?? $_POST['procurement_id'] ?? 0);
        $price = floatval($input['price'] ?? $_POST['price'] ?? 0);
        $deliveryDays = intval($input['delivery_days'] ?? $_POST['delivery_days'] ?? 0);
        $remarks = sanitize($input['remarks'] ?? $_POST['remarks'] ?? '');
        $proposalPath = uploadFile($_FILES['proposal_document'] ?? null);
        if (!$procurementId || $price <= 0 || $deliveryDays <= 0) {
            jsonResponse(['error' => 'Valid procurement, price, and delivery timeline are required.'], 422);
        }
        $stmt = $pdo->prepare('INSERT INTO bids (procurement_id, supplier_id, price, delivery_days, remarks, proposal_document, status, delivery_status, created_at) VALUES (?, ?, ?, ?, ?, ?, "pending", "unknown", NOW())');
        $stmt->execute([$procurementId, $_SESSION['user_id'], $price, $deliveryDays, $remarks, $proposalPath]);
        $stmt = $pdo->prepare('SELECT title FROM procurements WHERE id = ?');
        $stmt->execute([$procurementId]);
        $procurement = $stmt->fetch();
        logAction($_SESSION['user_id'], 'Submitted bid', 'Procurement ID: ' . $procurementId);
        if ($procurement) {
            notifySupplier($_SESSION['user_id'], 'Bid Submitted', "Your bid for '{$procurement['title']}' has been received and is under review.");
        }
        jsonResponse(['message' => 'Bid submitted successfully.']);
    }
}

jsonResponse(['error' => 'Method not allowed'], 405);
