<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$token = trim($_GET['token'] ?? '');
$userData = validateResetToken($token);
if (!$userData) {
    flash('This password reset link is invalid or expired.', 'error');
    header('Location: forgot_password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    if ($password === '' || $confirm === '') {
        flash('Please enter and confirm your new password.', 'error');
    } elseif ($password !== $confirm) {
        flash('Passwords do not match.', 'error');
    } else {
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $userData['user_id']]);
        markTokenAsUsed($token);
        flash('Your password has been reset. Please sign in.');
        header('Location: supplier_login.php');
        exit;
    }
}

renderGuestPageStart('Reset Password');
?>
<form action="reset_password.php?token=<?= htmlspecialchars(urlencode($token)) ?>" method="post" class="space-y-6">
  <div class="form-control">
    <label class="block text-sm font-medium text-slate-700">New password</label>
    <input type="password" name="password" required class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
  </div>
  <div class="form-control">
    <label class="block text-sm font-medium text-slate-700">Confirm password</label>
    <input type="password" name="confirm_password" required class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
  </div>
  <button type="submit" class="w-full rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Reset password</button>
</form>
<?php
renderGuestPageEnd();
