<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '') {
        flash('Please enter your email address.', 'error');
    } else {
        $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $token = generateResetToken($user['id']);
            sendTemplateEmail($email, 'password_reset', [
                'RESET_TOKEN' => $token,
                'USER_NAME' => $user['name'],
            ]);
        }
        flash('If this email exists in our system, a password reset link has been sent.', 'success');
    }
}

renderGuestPageStart('Forgot Password');
?>
<form action="forgot_password.php" method="post" class="space-y-6">
  <div class="form-control">
    <label class="block text-sm font-medium text-slate-700">Email address</label>
    <input type="email" name="email" required value="<?= htmlspecialchars($email) ?>" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
  </div>
  <button type="submit" class="w-full rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Send reset link</button>
</form>
<div class="mt-6 text-center text-sm text-slate-500">
  <p>Remembered your password? <a href="supplier_login.php" class="font-semibold text-slate-900 hover:underline">Sign in</a></p>
</div>
<?php
renderGuestPageEnd();
