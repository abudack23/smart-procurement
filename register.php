<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$name = '';
$email = '';
$company = '';
$services = '';
$experience = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $company = trim($_POST['company_name'] ?? '');
    $services = trim($_POST['services_offered'] ?? '');
    $experience = trim($_POST['past_experience'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        flash('Name, email, and password are required.', 'error');
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            flash('That email is already registered.', 'error');
        } else {
            // Public registrations must always create supplier accounts.
            $role = 'supplier';
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, company_name, services_offered, past_experience, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $company ?: null, $services ?: null, $experience ?: null]);
            flash('Registration successful. You can now sign in.');
            header('Location: supplier_login.php');
            exit;
        }
    }
}

renderGuestPageStart('Register');
?>
<div class="mb-4">
  <a href="index.php" class="inline-flex items-center gap-2 text-sm font-semibold text-[#1e0178] hover:underline">&larr; Back to Home</a>
</div>
<form action="register.php" method="post" class="space-y-6">
  <div class="form-control">
    <label class="block text-sm font-medium text-slate-700">Full name</label>
    <input type="text" name="name" required value="<?= htmlspecialchars($name) ?>" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
  </div>
  <div class="form-control">
    <label class="block text-sm font-medium text-slate-700">Email address</label>
    <input type="email" name="email" required value="<?= htmlspecialchars($email) ?>" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
  </div>
  <div class="form-control">
    <label class="block text-sm font-medium text-slate-700">Password</label>
    <input type="password" name="password" required class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
  </div>
  <div class="grid gap-4 sm:grid-cols-2">
    <div class="form-control">
      <label class="block text-sm font-medium text-slate-700">Company name</label>
      <input type="text" name="company_name" value="<?= htmlspecialchars($company) ?>" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
    </div>
    <div class="form-control">
      <label class="block text-sm font-medium text-slate-700">Services offered</label>
      <input type="text" name="services_offered" value="<?= htmlspecialchars($services) ?>" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
    </div>
  </div>
  <div class="form-control">
    <label class="block text-sm font-medium text-slate-700">Past experience</label>
    <textarea name="past_experience" rows="3" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200"><?= htmlspecialchars($experience) ?></textarea>
  </div>
  <button type="submit" class="w-full rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Create supplier account</button>
</form>
<div class="mt-6 text-center text-sm text-slate-500">
  <p>Already have an account? <a href="supplier_login.php" class="font-semibold text-slate-900 hover:underline">Sign in</a></p>
</div>
<?php
renderGuestPageEnd();
