<?php
require_once __DIR__ . '/includes/functions.php';
session_destroy();
header('Location: supplier_login.php');
exit;
