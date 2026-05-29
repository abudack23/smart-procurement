<?php
require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jsonResponse(['user' => $_SESSION['user'] ?? null]);
}

$input = getJsonInput();
$action = $input['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'login') {
        $email = sanitize($input['email'] ?? '');
        $password = $input['password'] ?? '';
        if (!$email || !$password) {
            jsonResponse(['error' => 'Email and password are required.'], 422);
        }
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            jsonResponse(['error' => 'Invalid credentials.'], 401);
        }
        unset($user['password_hash']);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;
        jsonResponse(['user' => $user]);
    }

    if ($action === 'register') {
        $name = sanitize($input['name'] ?? '');
        $email = sanitize($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (!$name || !$email || !$password) {
            jsonResponse(['error' => 'Name, email, and password are required.'], 422);
        }
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'Email is already registered.'], 409);
        }

        if (!isLoggedIn() || !in_array($_SESSION['user']['role'] ?? '', ['admin', 'superadmin'], true)) {
            $role = 'supplier';
        } else {
            $role = sanitize($input['role'] ?? 'supplier');
            if (!in_array($role, ['admin', 'supplier'], true)) {
                $role = 'supplier';
            }
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$name, $email, $passwordHash, $role]);
        $userId = $pdo->lastInsertId();
        logAction($userId, 'Account created', "User registered with role: $role");
        $variables = ['USER_NAME' => $name, 'USER_EMAIL' => $email, 'USER_ROLE' => ucfirst($role)];
        sendTemplateEmail($email, 'welcome', $variables);
        storeNotification($userId, 'Welcome', 'Your account has been successfully created. Login to get started!', 'success');
        jsonResponse(['message' => 'Registration successful. Please login.']);
    }

    if ($action === 'forgot_password') {
        $email = sanitize($input['email'] ?? '');
        if (!$email) {
            jsonResponse(['error' => 'Email is required.'], 422);
        }

        $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $token = generateResetToken($user['id']);
            $variables = ['RESET_TOKEN' => $token, 'USER_NAME' => $user['name']];
            sendTemplateEmail($email, 'password_reset', $variables);
        }

        jsonResponse(['message' => 'If that email is registered, a password reset link has been sent.']);
    }

    if ($action === 'reset_password') {
        $token = $input['token'] ?? '';
        $password = $input['password'] ?? '';

        if (!$token || !$password) {
            jsonResponse(['error' => 'Reset token and new password are required.'], 422);
        }

        $resetData = validateResetToken($token);
        if (!$resetData) {
            jsonResponse(['error' => 'Invalid or expired reset token.'], 400);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$passwordHash, $resetData['user_id']]);
        markTokenAsUsed($token);
        logAction($resetData['user_id'], 'Password reset', 'User successfully reset password');

        jsonResponse(['message' => 'Password has been reset successfully.']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    session_unset();
    session_destroy();
    jsonResponse(['message' => 'Logged out successfully']);
}

jsonResponse(['error' => 'Invalid request'], 400);
