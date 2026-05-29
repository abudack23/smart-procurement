<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = in_array($_POST['role'] ?? 'supplier', ['admin','supplier'], true) ? $_POST['role'] : 'supplier';
    $password = trim($_POST['password'] ?? '');
    if ($name === '' || $email === '' || $password === '') {
        flash('Name, email, and password are required.', 'error');
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            flash('Email already registered.', 'error');
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            logAction($_SESSION['user_id'], 'Created user', $email . ' as ' . $role);
            flash('User created successfully.');
            header('Location: users.php');
            exit;
        }
    }
}

if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'delete') {
        if ($id === $_SESSION['user_id']) {
            flash('You cannot delete your own account.', 'error');
        } else {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
            logAction($_SESSION['user_id'], 'Deleted user', 'User ID: ' . $id);
            flash('User removed.');
        }
        header('Location: users.php');
        exit;
    }
    if ($_GET['action'] === 'promote') {
        $newRole = $_GET['role'] === 'admin' ? 'admin' : 'supplier';
        $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$newRole, $id]);
        logAction($_SESSION['user_id'], 'Changed user role', 'User ID: ' . $id . ' -> ' . $newRole);
        flash('User role updated.');
        header('Location: users.php');
        exit;
    }
}

$search = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';
$pg = getPaginationParams(10);
$page = $pg['page'];
$perPage = $pg['per_page'];
$offset = $pg['offset'];

$where = 'WHERE 1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (name LIKE ? OR email LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}
if ($roleFilter === 'supplier' || $roleFilter === 'admin') {
    $where .= ' AND role = ?';
    $params[] = $roleFilter;
}
$orderBy = 'created_at DESC';
if ($sort === 'oldest') {
    $orderBy = 'created_at ASC';
} elseif ($sort === 'name') {
    $orderBy = 'name ASC';
} elseif ($sort === 'role') {
    $orderBy = 'role ASC';
}

$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM users ' . $where);
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$query = 'SELECT id, name, email, role, created_at FROM users ' . $where . ' ORDER BY ' . $orderBy . ' LIMIT ? OFFSET ?';
$stmt = $pdo->prepare($query);
$bindIndex = 1;
foreach ($params as $param) {
    $stmt->bindValue($bindIndex++, $param, PDO::PARAM_STR);
}
$stmt->bindValue($bindIndex++, (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue($bindIndex++, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

renderAuthPageStart('Users', 'users');
?>
<div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
  <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="text-xl font-semibold text-slate-900">Manage users</h2>
        <p class="text-sm text-slate-500">Create admin or supplier accounts and manage access.</p>
      </div>
      <form method="get" class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <label class="sr-only" for="search">Search users</label>
        <input id="search" name="search" type="text" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email" class="min-w-[220px] rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
        <label class="sr-only" for="role">Role filter</label>
        <select id="role" name="role" class="rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200">
          <option value="all" <?= $roleFilter === 'all' ? 'selected' : '' ?>>All roles</option>
          <option value="supplier" <?= $roleFilter === 'supplier' ? 'selected' : '' ?>>Suppliers</option>
          <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admins</option>
        </select>
        <label class="sr-only" for="sort">Sort users</label>
        <select id="sort" name="sort" class="rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
          <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
          <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name</option>
          <option value="role" <?= $sort === 'role' ? 'selected' : '' ?>>Role</option>
        </select>
        <button type="submit" class="rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
      </form>
    </div>
    <div class="mt-6 overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="sticky top-0 z-10 px-4 py-3 font-medium text-slate-500 bg-slate-50">Name</th>
            <th class="sticky top-0 z-10 px-4 py-3 font-medium text-slate-500 bg-slate-50">Email</th>
            <th class="sticky top-0 z-10 px-4 py-3 font-medium text-slate-500 bg-slate-50">Role</th>
            <th class="sticky top-0 z-10 px-4 py-3 font-medium text-slate-500 bg-slate-50">Member since</th>
            <th class="sticky top-0 z-10 px-4 py-3 font-medium text-slate-500 bg-slate-50 min-w-[180px]">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          <?php foreach ($users as $u): ?>
            <tr class="hover:bg-slate-50 odd:bg-white even:bg-slate-50">
              <td class="px-4 py-4 font-medium text-slate-900"><?= htmlspecialchars($u['name']) ?></td>
              <td class="px-4 py-4 text-slate-600"><?= htmlspecialchars($u['email']) ?></td>
              <td class="px-4 py-4 text-slate-600"><?= htmlspecialchars($u['role']) ?></td>
              <td class="px-4 py-4 text-slate-600"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
              <td class="px-4 py-4">
                <div class="flex flex-wrap items-center gap-2 min-w-[180px]">
                  <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                    <?php if ($u['role'] === 'supplier'): ?>
                      <a href="users.php?action=promote&id=<?= $u['id'] ?>&role=admin" class="inline-flex min-w-[120px] items-center justify-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 whitespace-nowrap">Make admin</a>
                    <?php else: ?>
                      <a href="users.php?action=promote&id=<?= $u['id'] ?>&role=supplier" class="inline-flex min-w-[120px] items-center justify-center rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600 whitespace-nowrap">Demote</a>
                    <?php endif; ?>
                    <a href="users.php?action=delete&id=<?= $u['id'] ?>" class="inline-flex min-w-[120px] items-center justify-center rounded-full bg-rose-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-600 whitespace-nowrap">Delete</a>
                  <?php else: ?>
                    <span class="text-sm text-slate-500">(you)</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php renderPaginationControls('users.php', $page, $perPage, $total); ?>
  </section>
  <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-xl font-semibold text-slate-900">Create user</h2>
    <p class="text-sm text-slate-500">Admins can create supplier or admin accounts here.</p>
    <form action="users.php" method="post" class="mt-6 space-y-4">
      <div>
        <label class="block text-sm font-medium text-slate-700">Full name</label>
        <input type="text" name="name" required class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900" />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Email</label>
        <input type="email" name="email" required class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900" />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Role</label>
        <select name="role" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
          <option value="supplier">Supplier</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Password</label>
        <input type="password" name="password" required class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900" />
      </div>
      <button type="submit" name="create_user" class="w-full rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Create account</button>
    </form>
  </section>
</div>
<?php
renderAuthPageEnd();
