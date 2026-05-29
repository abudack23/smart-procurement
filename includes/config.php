<?php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'smart_procurement';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// SMTP configuration (set $SMTP_ENABLED = true after filling credentials)
$SMTP_ENABLED = false;
$SMTP_HOST = 'smtp.gmail.com';
$SMTP_PORT = 587;
$SMTP_USER = ''; // your Gmail address (or SMTP username)
$SMTP_PASS = ''; // app password or SMTP password
$SMTP_SECURE = 'tls'; // 'tls' or 'ssl'
$SMTP_FROM_EMAIL = 'no-reply@smartprocurement.local';
$SMTP_FROM_NAME = 'Smart Procurement';

// Development: simulate sending emails by writing to a log file instead of SMTP
// Set to true to enable simulation (useful when SMTP creds are not provided)
$MAIL_SIMULATE = true;

