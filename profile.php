<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';
requireLogin();
$user = currentUser();

$name = $user['name'];
$email = $user['email'];
$company = '';
$services = '';
$experience = '';

$stmt = $pdo->prepare('SELECT company_name, services_offered, past_experience, created_at FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();
if ($profile) {
    $company = $profile['company_name'] ?? '';
    $services = $profile['services_offered'] ?? '';
    $experience = $profile['past_experience'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $company = trim($_POST['company_name'] ?? '');
    $services = trim($_POST['services_offered'] ?? '');
    $experience = trim($_POST['past_experience'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if ($name === '' || $email === '') {
        flash('Name and email are required.', 'error');
    } elseif ($password !== '' && $password !== $confirm) {
        flash('Passwords do not match.', 'error');
    } else {
        $sql = 'UPDATE users SET name = ?, email = ?, company_name = ?, services_offered = ?, past_experience = ?';
        $params = [$name, $email, $company ?: null, $services ?: null, $experience ?: null];
        if ($password !== '') {
            $sql .= ', password_hash = ?';
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id = ?';
        $params[] = $user['id'];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        flash('Profile updated successfully.');
        header('Location: profile.php');
        exit;
    }
}

renderAuthPageStart('Profile', 'profile');
?>
<div class="grid gap-6 xl:grid-cols-2">
  <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-xl font-semibold text-slate-900">Account details</h2>
    <p class="mt-2 text-sm text-slate-500">Update your personal and company information.</p>
    <form action="profile.php" method="post" class="mt-6 space-y-5">
      <div>
        <label class="block text-sm font-medium text-slate-700">Full name</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($name) ?>" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Email address</label>
        <input type="email" name="email" required value="<?= htmlspecialchars($email) ?>" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Company name</label>
        <input type="text" name="company_name" value="<?= htmlspecialchars($company) ?>" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Services offered</label>
        <input type="text" name="services_offered" value="<?= htmlspecialchars($services) ?>" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Past experience</label>
        <textarea name="past_experience" rows="4" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200"><?= htmlspecialchars($experience) ?></textarea>
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="block text-sm font-medium text-slate-700">New password</label>
          <input type="password" name="password" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Confirm password</label>
          <input type="password" name="confirm_password" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
        </div>
      </div>
      <button type="submit" class="w-full rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Save changes</button>
    </form>
  </section>
  <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-xl font-semibold text-slate-900">Account summary</h2>
    <div class="mt-6 space-y-4 text-sm text-slate-600">
      <div>
        <p class="font-semibold text-slate-900">Role</p>
        <p><?= htmlspecialchars($user['role']) ?></p>
      </div>
      <div>
        <p class="font-semibold text-slate-900">Member since</p>
        <p><?= htmlspecialchars(date('F j, Y', strtotime($profile['created_at'] ?? 'now'))) ?></p>
      </div>
      <div>
        <p class="font-semibold text-slate-900">Notifications</p>
        <p><?= getUnreadNotificationCount($user['id']) ?> unread</p>
      </div>
    </div>
  </section>
</div>
<?php
renderAuthPageEnd();
