<?php
require_once __DIR__ . '/../includes/functions.php';

$to = 'chanchan122301@gmail.com';
$variables = [
    'USER_NAME' => 'Chanchan',
    'USER_EMAIL' => $to,
    'USER_ROLE' => 'Supplier'
];

echo "Sending test email to: $to\n";
$ok = sendTemplateEmail($to, 'welcome', $variables);
if ($ok) {
    echo "Result: Mail function reported success.\n";
} else {
    echo "Result: Mail function reported failure. Check XAMPP SMTP settings.\n";
}

// Exit with non-zero code on failure so CI/runner can detect it
exit($ok ? 0 : 1);
