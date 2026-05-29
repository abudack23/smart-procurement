<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_procurement'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budget = trim($_POST['budget'] ?? '');
    $delivery_days = (int)($_POST['delivery_days'] ?? 0);
    $deadline = trim($_POST['submission_deadline'] ?? '');
    $criteria = trim($_POST['evaluation_criteria'] ?? '');
    if ($title === '' || $description === '' || $delivery_days <= 0 || $deadline === '') {
        flash('Title, description, deadline, and delivery timeline are required.', 'error');
    } else {
        $stmt = $pdo->prepare('INSERT INTO procurements (title, description, budget, delivery_days, submission_deadline, evaluation_criteria, status, created_at) VALUES (?, ?, ?, ?, ?, ?, "open", NOW())');
        $stmt->execute([$title, $description, $budget ?: null, $delivery_days, $deadline, $criteria ?: null]);
        $procurementId = $pdo->lastInsertId();
        logAction($_SESSION['user_id'], 'Created procurement', 'Procurement ID: ' . $procurementId);
        notifyAdminUsers('New procurement created', 'A new procurement opportunity has been created: ' . $title);
        notifyAllSuppliers('New procurement available', 'A new procurement has been posted: ' . $title);
        flash('Procurement has been created successfully.');
        header('Location: procurements.php');
        exit;
    }
}

if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'close') {
        $stmt = $pdo->prepare('UPDATE procurements SET status = "closed" WHERE id = ?');
        $stmt->execute([$id]);
        logAction($_SESSION['user_id'], 'Closed procurement', 'Procurement ID: ' . $id);
        flash('Procurement closed successfully.');
        header('Location: procurements.php');
        exit;
    }
    if ($_GET['action'] === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM procurements WHERE id = ?');
        $stmt->execute([$id]);
        logAction($_SESSION['user_id'], 'Deleted procurement', 'Procurement ID: ' . $id);
        flash('Procurement removed successfully.');
        header('Location: procurements.php');
        exit;
    }
}

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$pg = getPaginationParams(10);
$page = $pg['page'];
$perPage = $pg['per_page'];
$offset = $pg['offset'];

$where = 'WHERE 1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (p.title LIKE ? OR p.description LIKE ? OR p.evaluation_criteria LIKE ? OR p.budget LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

$orderBy = 'p.created_at DESC';
if ($sort === 'oldest') {
    $orderBy = 'p.created_at ASC';
} elseif ($sort === 'budget_asc') {
    $orderBy = 'CAST(p.budget AS DECIMAL(12,2)) ASC';
} elseif ($sort === 'budget_desc') {
    $orderBy = 'CAST(p.budget AS DECIMAL(12,2)) DESC';
} elseif ($sort === 'status') {
    $orderBy = 'p.status ASC';
}

$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM procurements p ' . $where);
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$query = 'SELECT p.*, COUNT(b.id) AS bids FROM procurements p LEFT JOIN bids b ON b.procurement_id = p.id ' . $where . ' GROUP BY p.id ORDER BY ' . $orderBy . ' LIMIT ? OFFSET ?';
$stmt = $pdo->prepare($query);
$bindIndex = 1;
foreach ($params as $param) {
    $stmt->bindValue($bindIndex++, $param, PDO::PARAM_STR);
}
$stmt->bindValue($bindIndex++, (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue($bindIndex++, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$procurements = $stmt->fetchAll();

renderAuthPageStart('Procurements', 'procurements');
?>
<div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
  <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
      <div>
        <h2 class="text-xl font-semibold text-slate-900">Manage procurements</h2>
        <p class="text-sm text-slate-500">Create new opportunities and manage open bids.</p>
      </div>
      <form method="get" class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <label class="sr-only" for="search">Search procurements</label>
        <input id="search" name="search" type="text" value="<?= htmlspecialchars($search) ?>" placeholder="Search procures, budget or keywords" class="w-full min-w-[220px] rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
        <label class="sr-only" for="sort">Sort</label>
        <select id="sort" name="sort" class="rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
          <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
          <option value="budget_desc" <?= $sort === 'budget_desc' ? 'selected' : '' ?>>Budget high-low</option>
          <option value="budget_asc" <?= $sort === 'budget_asc' ? 'selected' : '' ?>>Budget low-high</option>
          <option value="status" <?= $sort === 'status' ? 'selected' : '' ?>>Status</option>
        </select>
        <button type="submit" class="rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
      </form>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="sticky top-0 z-10 px-4 py-3 font-medium text-slate-500 bg-slate-50">Title</th>
            <th class="sticky top-0 z-10 px-4 py-3 font-medium text-slate-500 bg-slate-50">Deadline</th>
            <th class="sticky top-0 z-10 px-4 py-3 font-medium text-slate-500 bg-slate-50">Status</th>
            <th class="sticky top-0 z-10 px-4 py-3 font-medium text-slate-500 bg-slate-50">Bids</th>
            <th class="sticky top-0 z-10 px-4 py-3 font-medium text-slate-500 bg-slate-50 min-w-[220px]">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          <?php foreach ($procurements as $procurement): ?>
            <tr class="hover:bg-slate-50 odd:bg-white even:bg-slate-50">
              <td class="px-4 py-4 font-medium text-slate-900"><?= htmlspecialchars($procurement['title']) ?></td>
              <td class="px-4 py-4 text-slate-600"><?= date('M j, Y', strtotime($procurement['submission_deadline'])) ?></td>
              <td class="px-4 py-4 text-slate-600"><?= ucfirst($procurement['status']) ?></td>
              <td class="px-4 py-4 text-slate-600"><?= $procurement['bids'] ?></td>
              <td class="px-4 py-4">
                <div class="flex flex-wrap items-center gap-2 min-w-[220px]">
                  <a href="view_bids.php?procurement_id=<?= $procurement['id'] ?>" class="inline-flex min-w-[110px] items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-100 whitespace-nowrap">View bids</a>
                  <a href="procurement_edit.php?id=<?= $procurement['id'] ?>" class="inline-flex min-w-[110px] items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-100 whitespace-nowrap">Edit</a>
                  <?php if ($procurement['status'] === 'open'): ?>
                    <a href="procurements.php?action=close&id=<?= $procurement['id'] ?>" class="inline-flex min-w-[110px] items-center justify-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 whitespace-nowrap">Close</a>
                  <?php endif; ?>
                  <a href="procurements.php?action=delete&id=<?= $procurement['id'] ?>" class="inline-flex min-w-[110px] items-center justify-center rounded-full bg-rose-500 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-600 whitespace-nowrap">Delete</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php renderPaginationControls('procurements.php', $page, $perPage, $total); ?>
  </section>
  <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-xl font-semibold text-slate-900">New procurement</h2>
    <p class="mt-2 text-sm text-slate-500">Create a new opportunity for suppliers to bid.</p>
    <form action="procurements.php" method="post" class="mt-6 space-y-5">
      <div>
        <label class="block text-sm font-medium text-slate-700">Title</label>
        <input type="text" name="title" required class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Description</label>
        <textarea name="description" rows="4" required class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200"></textarea>
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="block text-sm font-medium text-slate-700">Budget</label>
          <input type="text" name="budget" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Delivery days</label>
          <input type="number" name="delivery_days" min="1" required class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
        </div>
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="block text-sm font-medium text-slate-700">Submission deadline</label>
          <input type="date" name="submission_deadline" required class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Evaluation criteria</label>
          <input type="text" name="evaluation_criteria" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
        </div>
      </div>
      <button type="submit" name="create_procurement" class="w-full rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Create procurement</button>
    </form>
  </section>
</div>
<?php
renderAuthPageEnd();
