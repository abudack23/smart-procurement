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
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        flash('Please fill in both email and password.', 'error');
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            if (!in_array($user['role'], ['admin', 'superadmin'], true)) {
                flash('This login is only for admin accounts. Please use supplier login.', 'error');
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ];
                flash('Welcome back, ' . $user['name'] . '!');
                header('Location: dashboard.php');
                exit;
            }
        } else {
            flash('Invalid email or password.', 'error');
        }
    }
}

renderGuestPageStart('Admin Login');
?>
<form action="admin_login.php" method="post" class="space-y-6">
  <div class="form-control">
    <label class="block text-sm font-medium text-slate-700">Email</label>
    <input type="email" name="email" required value="<?= htmlspecialchars($email) ?>" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
  </div>
  <div class="form-control">
    <label class="block text-sm font-medium text-slate-700">Password</label>
    <input type="password" name="password" required class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
  </div>
  <button type="submit" class="w-full rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Sign in</button>
</form>
<div class="mt-6 text-center text-sm text-slate-500">
  <p><a href="supplier_login.php" class="font-semibold text-slate-900 hover:underline">Go to supplier login</a></p>
</div>
<?php
renderGuestPageEnd();
