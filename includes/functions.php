<?php
require_once __DIR__ . '/config.php';

session_start();

function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /Smart-Procurement/index.php');
        exit;
    }
}

function requireRole($role) {
    if (!isLoggedIn()) {
        header('Location: /Smart-Procurement/index.php');
        exit;
    }
    $userRole = $_SESSION['user']['role'] ?? '';
    if ($role === 'admin') {
        if (!in_array($userRole, ['admin', 'superadmin'], true)) {
            header('Location: /Smart-Procurement/index.php');
            exit;
        }
    } else {
        if ($userRole !== $role) {
            header('Location: /Smart-Procurement/index.php');
            exit;
        }
    }
}

function flash($message, $type = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getUserStats($userId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total_bids, SUM(CASE WHEN status = "awarded" THEN 1 ELSE 0 END) AS wins FROM bids WHERE supplier_id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function uploadFile($file, $pathPrefix = 'uploads/') {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $fileName = time() . '-' . basename($file['name']);
    $targetDir = __DIR__ . '/../' . rtrim($pathPrefix, '/');
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0755, true);
    }
    $destination = $targetDir . '/' . $fileName;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return null;
    }
    return rtrim($pathPrefix, '/') . '/' . $fileName;
}

function countBidders() {
    global $pdo;
    $stmt = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "supplier"');
    return (int)$stmt->fetchColumn();
}

function countProcurements() {
    global $pdo;
    $stmt = $pdo->query('SELECT COUNT(*) FROM procurements');
    return (int)$stmt->fetchColumn();
}

function countOngoingBids() {
    global $pdo;
    $stmt = $pdo->query('SELECT COUNT(*) FROM bids WHERE status = "pending"');
    return (int)$stmt->fetchColumn();
}

function countCompletedBids() {
    global $pdo;
    $stmt = $pdo->query('SELECT COUNT(*) FROM bids WHERE status IN ("awarded","rejected")');
    return (int)$stmt->fetchColumn();
}

// Pagination helpers
function getPaginationParams($defaultPerPage = 10) {
    $allowed = [10,25,50,100];
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = (int)($_GET['per_page'] ?? $defaultPerPage);
    if (!in_array($perPage, $allowed, true)) {
        $perPage = $defaultPerPage;
    }
    $offset = max(0, ($page - 1) * $perPage);
    return ['page' => $page, 'per_page' => $perPage, 'offset' => $offset];
}

function renderPaginationControls($baseUrl, $page, $perPage, $total, $visible = 5) {
    $page = (int)$page;
    $perPage = (int)$perPage;
    $total = (int)$total;
    $visible = (int)$visible;

    if ($page < 1) {
        $page = 1;
    }
    if ($perPage <= 0) {
        $perPage = 10;
    }
    if ($visible <= 0) {
        $visible = 5;
    }

    $totalPages = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $start = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
    $end = $total === 0 ? 0 : min($total, $page * $perPage);

    $params = $_GET;
    unset($params['page'], $params['per_page']);
    $preserve = '';
    foreach ($params as $k => $v) {
        if ($v === null || $v === '') {
            continue;
        }
        $preserve .= '&' . urlencode($k) . '=' . urlencode($v);
    }

    $querySep = strpos($baseUrl, '?') === false ? '?' : '&';
    $hrefPrefix = $baseUrl . $querySep;

    echo '<div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">';
    echo '<div class="text-sm text-slate-500">Showing ' . ($total === 0 ? '0' : $start) . '&ndash;' . ($total === 0 ? '0' : $end) . ' of ' . $total . ' records</div>';

    echo '<div class="flex items-center gap-2">';
    echo '<form method="get" class="inline-flex items-center">';
    foreach ($params as $k => $v) {
        echo '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars($v) . '" />';
    }
    echo '<label class="sr-only" for="per_page">Rows per page</label>';
    echo '<select name="per_page" id="per_page" onchange="this.form.submit()" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-sm">';
    foreach ([10,25,50,100] as $option) {
        $sel = $option === $perPage ? ' selected' : '';
        echo "<option value=\"$option\"$sel>$option</option>";
    }
    echo '</select>';
    echo '</form>';

    echo '<nav class="inline-flex items-center rounded-md bg-white" aria-label="Pagination">';
    $firstDisabled = $page <= 1 ? ' opacity-50 pointer-events-none' : '';
    $lastDisabled = $page >= $totalPages ? ' opacity-50 pointer-events-none' : '';
    echo '<a href="' . $hrefPrefix . 'page=1&per_page=' . $perPage . $preserve . '" class="px-3 py-1 text-sm text-slate-600 border rounded-md' . $firstDisabled . '">« First</a>';
    $prev = max(1, $page - 1);
    echo '<a href="' . $hrefPrefix . 'page=' . $prev . '&per_page=' . $perPage . $preserve . '" class="px-3 py-1 text-sm text-slate-600 border rounded-md' . $firstDisabled . '">‹ Prev</a>';

    $half = (int)floor($visible / 2);
    $startPage = max(1, $page - $half);
    $endPage = min($totalPages, $startPage + $visible - 1);
    for ($p = $startPage; $p <= $endPage; $p++) {
        $active = $p === $page ? ' bg-slate-900 text-white' : ' text-slate-700 bg-white';
        echo '<a href="' . $hrefPrefix . 'page=' . $p . '&per_page=' . $perPage . $preserve . '" class="px-3 py-1 text-sm border rounded-md' . $active . '">' . $p . '</a>';
    }

    $next = min($totalPages, $page + 1);
    echo '<a href="' . $hrefPrefix . 'page=' . $next . '&per_page=' . $perPage . $preserve . '" class="px-3 py-1 text-sm text-slate-600 border rounded-md' . $lastDisabled . '">Next ›</a>';
    echo '<a href="' . $hrefPrefix . 'page=' . $totalPages . '&per_page=' . $perPage . $preserve . '" class="px-3 py-1 text-sm text-slate-600 border rounded-md' . $lastDisabled . '">Last »</a>';
    echo '</nav>';

    echo '</div></div>';
}

function sendEmail($to, $subject, $message) {
    global $SMTP_ENABLED;
    if (!empty($SMTP_ENABLED)) {
        return sendMailSMTP($to, $subject, $message);
    }
    global $MAIL_SIMULATE;
    if (!empty($MAIL_SIMULATE)) {
        // Append the email to a log file for development/testing
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/email_log.txt';
        $entry = "-----\nTo: $to\nSubject: $subject\n\n$message\n-----\n";
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        logAction(null, 'Email simulated', "To: $to Subject: $subject");
        return true;
    }
    $headers = "From: no-reply@smartprocurement.local\r\n" .
               "Content-Type: text/plain; charset=UTF-8\r\n";
    if (@mail($to, $subject, $message, $headers)) {
        return true;
    }
    logAction(null, 'Email failure', "To: $to Subject: $subject");
    return false;
}

function sendMailSMTP($to, $subject, $message) {
    // Use PHPMailer from vendor if available
    $base = __DIR__ . '/../vendor/PHPMailer-master/src/';
    if (!file_exists($base . 'PHPMailer.php')) {
        logAction(null, 'SMTP failure', 'PHPMailer library not found');
        return false;
    }
    require_once $base . 'PHPMailer.php';
    require_once $base . 'SMTP.php';
    require_once $base . 'Exception.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    global $SMTP_HOST, $SMTP_PORT, $SMTP_USER, $SMTP_PASS, $SMTP_SECURE, $SMTP_FROM_EMAIL, $SMTP_FROM_NAME;
    try {
        $mail->isSMTP();
        $mail->Host = $SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = $SMTP_USER;
        $mail->Password = $SMTP_PASS;
        if (!empty($SMTP_SECURE)) {
            $mail->SMTPSecure = $SMTP_SECURE;
        }
        $mail->Port = $SMTP_PORT;
        $mail->setFrom($SMTP_FROM_EMAIL, $SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = $message;
        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        logAction(null, 'SMTP Email failure', $e->getMessage());
        return false;
    }
}

function loadEmailTemplate($template, $variables = []) {
    $path = __DIR__ . '/../templates/' . $template . '.txt';
    if (!file_exists($path)) {
        return null;
    }
    $content = file_get_contents($path);
    foreach ($variables as $key => $value) {
        $content = str_replace('{' . $key . '}', htmlspecialchars($value), $content);
    }
    return $content;
}

function sendTemplateEmail($to, $templateName, $variables = []) {
    $template = loadEmailTemplate($templateName, $variables);
    if (!$template) {
        return false;
    }
    $lines = explode("\n", $template, 2);
    $subject = str_replace('SUBJECT: ', '', trim($lines[0]));
    $message = trim($lines[1]);
    return sendEmail($to, $subject, $message);
}

function storeNotification($userId, $title, $message, $type = 'info') {
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())');
    $stmt->execute([$userId, $title, $message, $type]);
}

function notifySupplier($supplierId, $subject, $message, $templateName = null, $variables = []) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT email, name FROM users WHERE id = ? AND role = "supplier"');
    $stmt->execute([$supplierId]);
    $user = $stmt->fetch();
    if (!$user) {
        return false;
    }
    if ($templateName) {
        $emailBody = loadEmailTemplate($templateName, $variables);
        if ($emailBody) {
            $lines = explode("\n", $emailBody, 2);
            $emailSubject = str_replace('SUBJECT: ', '', trim($lines[0]));
            $message = trim($lines[1]);
            $sent = sendEmail($user['email'], $emailSubject, $message);
        }
    } else {
        $body = "Hello " . $user['name'] . ",\n\n" . $message . "\n\nRegards,\nSmart Procurement System";
        $sent = sendEmail($user['email'], $subject, $body);
    }
    if ($sent) {
        logAction($supplierId, 'Notification sent', $subject);
        storeNotification($supplierId, $subject, $message, 'info');
        return true;
    }
    return false;
}

function notifyAllSuppliers($subject, $message) {
    global $pdo;
    $stmt = $pdo->query('SELECT id FROM users WHERE role = "supplier"');
    while ($supplier = $stmt->fetch()) {
        notifySupplier($supplier['id'], $subject, $message);
    }
}

function logAction($userId, $action, $details = null) {
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$userId, $action, $details]);
}

function getUnreadNotificationCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function generateResetToken($userId) {
    global $pdo;
    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())');
    $stmt->execute([$userId, hash('sha256', $token)]);
    return $token;
}

function validateResetToken($token) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW() AND used_at IS NULL LIMIT 1');
    $stmt->execute([hash('sha256', $token)]);
    return $stmt->fetch();
}

function markTokenAsUsed($token) {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE token = ?');
    $stmt->execute([hash('sha256', $token)]);
}

function isAdminExists() {
    global $pdo;
    $stmt = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "admin"');
    return (int)$stmt->fetchColumn() > 0;
}

function getRecentProcurements($limit = 5) {
    global $pdo;
    $limit = (int)$limit;
    $stmt = $pdo->prepare('SELECT * FROM procurements ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getOpenProcurements($limit = 10) {
    global $pdo;
    $limit = (int)$limit;
    $stmt = $pdo->prepare('SELECT * FROM procurements WHERE status = "open" ORDER BY submission_deadline ASC LIMIT ?');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getProcurementById($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM procurements WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getSupplierBids($userId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT b.*, p.title, p.id AS procurement_id FROM bids b JOIN procurements p ON p.id = b.procurement_id WHERE b.supplier_id = ? ORDER BY b.created_at DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getRecentNotifications($userId, $limit = 20) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function markNotificationsRead($userId) {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
    $stmt->execute([$userId]);
}

function markNotificationRead($notificationId, $userId) {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([(int)$notificationId, (int)$userId]);
}

function getTopSuppliers($limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT u.id, u.name, COUNT(b.id) AS total_bids, SUM(b.status = "awarded") AS awards FROM users u JOIN bids b ON b.supplier_id = u.id WHERE u.role = "supplier" GROUP BY u.id ORDER BY awards DESC, total_bids DESC LIMIT ?');
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSupplierRankings($limit = 5) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT u.id, u.name, u.email FROM users u WHERE u.role = "supplier" ORDER BY u.created_at DESC LIMIT ?');
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($suppliers as &$supplier) {
        $metrics = getSupplierPerformanceMetrics($supplier['id']);
        $supplier = array_merge($supplier, $metrics);
    }
    unset($supplier);
    usort($suppliers, function($a, $b) {
        return $b['reliability_score'] <=> $a['reliability_score'];
    });
    return array_slice($suppliers, 0, $limit);
}

function getTotalProcurementBudget() {
    global $pdo;
    $stmt = $pdo->query('SELECT SUM(CAST(budget AS DECIMAL(12,2))) AS total_budget FROM procurements WHERE budget REGEXP "^[0-9]+(\\.[0-9]+)?$"');
    $result = $stmt->fetch();
    return $result['total_budget'] ? floatval($result['total_budget']) : 0.0;
}

function notifyAdminUsers($title, $message) {
    global $pdo;
    $stmt = $pdo->query('SELECT id FROM users WHERE role IN ("admin","superadmin")');
    while ($admin = $stmt->fetch()) {
        storeNotification($admin['id'], $title, $message, 'warning');
    }
}

function getBidSummary() {
    global $pdo;
    $stmt = $pdo->query('SELECT status, COUNT(*) AS count FROM bids GROUP BY status');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Supplier Performance Metrics
function getSupplierReliabilityStatus($supplierId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total_bids, SUM(CASE WHEN status = "awarded" THEN 1 ELSE 0 END) AS bids_won, SUM(CASE WHEN delivery_status = "on-time" THEN 1 ELSE 0 END) AS on_time_deliveries FROM bids WHERE supplier_id = ?');
    $stmt->execute([(int)$supplierId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result || (int)$result['total_bids'] === 0) {
        return [
            'score' => 0,
            'label' => 'Not Yet Rated',
            'has_history' => false,
            'tone' => 'neutral'
        ];
    }

    $winRate = ((int)$result['bids_won'] / (int)$result['total_bids']) * 100;
    $onTimeRate = ((int)$result['on_time_deliveries'] / (int)$result['total_bids']) * 100;
    $score = (int)min(100, round((40 * $winRate / 100) + (60 * $onTimeRate / 100) + 30));

    return [
        'score' => $score,
        'label' => $score >= 80 ? 'High Reliability' : ($score >= 60 ? 'Reliable' : 'Needs Review'),
        'has_history' => true,
        'tone' => $score >= 80 ? 'emerald' : ($score >= 60 ? 'amber' : 'rose')
    ];
}

function getSupplierPerformanceMetrics($supplierId) {
    global $pdo;
    $stmt = $pdo->prepare('
        SELECT 
            COUNT(*) AS total_bids,
            SUM(CASE WHEN status = "awarded" THEN 1 ELSE 0 END) AS bids_won,
            SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) AS bids_lost,
            SUM(CASE WHEN delivery_status = "on-time" THEN 1 ELSE 0 END) AS on_time_deliveries,
            AVG(CASE WHEN delivery_status IS NOT NULL THEN 1 ELSE 0 END) AS delivery_rating
        FROM bids 
        WHERE supplier_id = ?
    ');
    $stmt->execute([$supplierId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $reliability = getSupplierReliabilityStatus($supplierId);

    if (!$result || (int)$result['total_bids'] === 0) {
        return [
            'total_bids' => 0,
            'bids_won' => 0,
            'bids_lost' => 0,
            'on_time_deliveries' => 0,
            'delivery_rating' => 0,
            'win_rate' => 0,
            'on_time_rate' => 0,
            'reliability_score' => 0,
            'reliability_label' => $reliability['label'],
            'reliability_tone' => $reliability['tone'],
            'has_reliability_history' => false
        ];
    }
    
    $winRate = ((int)$result['bids_won'] / (int)$result['total_bids']) * 100;
    $onTimeRate = ((int)$result['on_time_deliveries'] / max(1, (int)$result['total_bids'])) * 100;
    $reliabilityScore = $reliability['score'];
    
    return array_merge($result, [
        'win_rate' => round($winRate, 2),
        'on_time_rate' => round($onTimeRate, 2),
        'reliability_score' => $reliabilityScore,
        'reliability_label' => $reliability['label'],
        'reliability_tone' => $reliability['tone'],
        'has_reliability_history' => $reliability['has_history']
    ]);
}

function getStatusBadgeClass($status) {
    $status = strtolower((string)$status);
    $map = [
        'awarded' => 'inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 shadow-sm ring-1 ring-emerald-200',
        'pending' => 'inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 shadow-sm ring-1 ring-amber-200',
        'rejected' => 'inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-800 shadow-sm ring-1 ring-rose-200',
        'recommended' => 'inline-flex items-center rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 px-3 py-1 text-xs font-semibold text-white shadow-sm',
        'high reliability' => 'inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 shadow-sm ring-1 ring-emerald-200',
        'medium reliability' => 'inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 shadow-sm ring-1 ring-amber-200',
        'low reliability' => 'inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-800 shadow-sm ring-1 ring-rose-200',
    ];
    return $map[$status] ?? 'inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200';
}

function getNotificationAccentClass($type) {
    $type = strtolower((string)$type);
    $map = [
        'warning' => 'border-amber-200 bg-amber-50/80',
        'success' => 'border-emerald-200 bg-emerald-50/80',
        'info' => 'border-slate-200 bg-slate-50',
        'error' => 'border-rose-200 bg-rose-50/80',
    ];
    return $map[$type] ?? 'border-slate-200 bg-slate-50';
}

function getNotificationIcon($type) {
    $type = strtolower((string)$type);
    $map = [
        'warning' => '⚠️',
        'success' => '✅',
        'info' => 'ℹ️',
        'error' => '❗',
    ];
    return $map[$type] ?? '🔔';
}

// Get recent procurement participation for supplier
function getSupplierRecentActivity($supplierId, $limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare('
        SELECT 
            b.id, b.price, b.delivery_days, b.status, b.delivery_status, b.created_at,
            p.id AS procurement_id, p.title, p.budget
        FROM bids b
        JOIN procurements p ON p.id = b.procurement_id
        WHERE b.supplier_id = ?
        ORDER BY b.created_at DESC
        LIMIT ?
    ');
    $stmt->bindValue(1, $supplierId, PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all bids for a procurement with supplier details
function getSupplierBidsWithDetails($procurementId) {
    global $pdo;
    $stmt = $pdo->prepare('
        SELECT 
            b.id, b.supplier_id, b.price, b.delivery_days, b.status, b.delivery_status, b.created_at,
            u.name AS supplier_name, u.email AS supplier_email
        FROM bids b
        JOIN users u ON u.id = b.supplier_id
        WHERE b.procurement_id = ?
        ORDER BY b.created_at DESC
    ');
    $stmt->execute([$procurementId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get ranked bids with AI scores for a procurement
function getRankedBidsForProcurement($procurementId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM procurements WHERE id = ? LIMIT 1');
    $stmt->execute([$procurementId]);
    $procurement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$procurement) {
        return [];
    }
    
    require_once __DIR__ . '/ai.php';
    
    $bids = getSupplierBidsWithDetails($procurementId);
    $rankedBids = [];
    
    foreach ($bids as $bid) {
        $evaluation = evaluateBid($bid, $procurement);
        $rankedBids[] = array_merge($bid, $evaluation);
    }
    
    // Sort by final score descending
    usort($rankedBids, function($a, $b) {
        return $b['final_score'] - $a['final_score'];
    });
    
    // Add rank badges
    foreach ($rankedBids as &$bid) {
        $bid['rank'] = 0;
        if ($bid['final_score'] == $rankedBids[0]['final_score']) {
            $bid['rank_badge'] = 'Best Overall';
        } else if ($bid['price'] == min(array_column($rankedBids, 'price'))) {
            $bid['rank_badge'] = 'Lowest Cost';
        } else if ($bid['delivery_days'] == min(array_column($rankedBids, 'delivery_days'))) {
            $bid['rank_badge'] = 'Fastest Delivery';
        } else if ($bid['reliability_score'] == max(array_column($rankedBids, 'reliability_score'))) {
            $bid['rank_badge'] = 'Most Reliable';
        }
    }
    unset($bid);
    
    return $rankedBids;
}
