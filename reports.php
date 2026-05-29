<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';
requireRole('admin');

$topSuppliers = getTopSuppliers();
$bidSummary = getBidSummary();
$totalSpendStmt = $pdo->query('SELECT SUM(price) AS total_spend, AVG(price) AS avg_bid FROM bids WHERE status = "awarded"');
$totalSpend = $totalSpendStmt->fetch();
$procurementStatsStmt = $pdo->query('SELECT COUNT(*) AS total_procurements, SUM(status = "closed") AS closed_procurements FROM procurements');
$procurementStats = $procurementStatsStmt->fetch();
$awardRate = $procurementStats['total_procurements'] ? round(($procurementStats['closed_procurements'] / $procurementStats['total_procurements']) * 100, 1) : 0;

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$pg = getPaginationParams(15);
$page = $pg['page'];
$perPage = $pg['per_page'];
$offset = $pg['offset'];

$where = 'WHERE 1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (a.action LIKE ? OR a.details LIKE ? OR u.name LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}
$orderBy = 'a.created_at DESC';
if ($sort === 'oldest') {
    $orderBy = 'a.created_at ASC';
} elseif ($sort === 'user') {
    $orderBy = 'u.name ASC';
} elseif ($sort === 'action') {
    $orderBy = 'a.action ASC';
}

$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ' . $where);
$totalStmt->execute($params);
$totalAudit = (int)$totalStmt->fetchColumn();

$auditStmt = $pdo->prepare('SELECT a.*, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ' . $where . ' ORDER BY ' . $orderBy . ' LIMIT ? OFFSET ?');
$bindIndex = 1;
foreach ($params as $param) {
    $auditStmt->bindValue($bindIndex++, $param, PDO::PARAM_STR);
}
$auditStmt->bindValue($bindIndex++, (int)$perPage, PDO::PARAM_INT);
$auditStmt->bindValue($bindIndex++, (int)$offset, PDO::PARAM_INT);
$auditStmt->execute();
$auditLogs = $auditStmt->fetchAll();

renderAuthPageStart('Reports', 'reports');
?>
<div class="grid gap-6 xl:grid-cols-3">
  <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-6">
      <h2 class="text-xl font-semibold text-slate-900">Procurement KPIs</h2>
      <p class="text-sm text-slate-500">Current platform-wide procurement performance.</p>
    </div>
    <div class="space-y-4 text-sm text-slate-600">
      <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
        <p class="font-semibold text-slate-900">Total procurements</p>
        <p class="mt-1"><?= $procurementStats['total_procurements'] ?></p>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
        <p class="font-semibold text-slate-900">Closed procurements</p>
        <p class="mt-1"><?= $procurementStats['closed_procurements'] ?></p>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
        <p class="font-semibold text-slate-900">Award completion rate</p>
        <p class="mt-1"><?= $awardRate ?>%</p>
      </div>
    </div>
  </section>

  <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-6">
      <h2 class="text-xl font-semibold text-slate-900">Cost analysis</h2>
      <p class="text-sm text-slate-500">Review actual awarded procurement spend.</p>
    </div>
    <div class="space-y-4 text-sm text-slate-600">
      <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
        <p class="font-semibold text-slate-900">Total awarded spend</p>
        <p class="mt-1">₱<?= number_format($totalSpend['total_spend'] ?: 0, 2) ?></p>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
        <p class="font-semibold text-slate-900">Average awarded bid</p>
        <p class="mt-1">₱<?= number_format($totalSpend['avg_bid'] ?: 0, 2) ?></p>
      </div>
    </div>
  </section>

  <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-6">
      <h2 class="text-xl font-semibold text-slate-900">Supplier performance</h2>
      <p class="text-sm text-slate-500">Top suppliers by awards and participation.</p>
    </div>
    <?php if (empty($topSuppliers)): ?>
      <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">No supplier bids have been recorded yet.</div>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($topSuppliers as $supplier): ?>
          <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
            <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($supplier['name']) ?></p>
            <p class="mt-2 text-sm text-slate-600">Total bids: <?= $supplier['total_bids'] ?> • Awards: <?= $supplier['awards'] ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<div class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
  <h3 class="text-lg font-semibold text-slate-900 mb-4">Bid status breakdown</h3>
  <div class="grid gap-4 sm:grid-cols-3">
    <?php foreach ($bidSummary as $summary): ?>
      <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
        <p class="font-semibold text-slate-900"><?= htmlspecialchars(ucfirst($summary['status'])) ?></p>
        <p class="mt-2">Count: <?= $summary['count'] ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <h3 class="text-lg font-semibold text-slate-900">Audit trail</h3>
      <p class="text-sm text-slate-500">Search and review logged admin actions.</p>
    </div>
    <form method="get" class="flex flex-col gap-3 sm:flex-row sm:items-center">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search audit logs" class="w-full min-w-[14rem] rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
      <select name="sort" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
        <option value="user" <?= $sort === 'user' ? 'selected' : '' ?>>User</option>
        <option value="action" <?= $sort === 'action' ? 'selected' : '' ?>>Action</option>
      </select>
      <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Filter</button>
    </form>
  </div>

  <div class="mt-6 overflow-x-auto">
    <table class="min-w-full border-collapse text-left text-sm">
      <thead class="bg-slate-100 text-slate-800">
        <tr>
          <th class="px-4 py-3 font-medium">Date</th>
          <th class="px-4 py-3 font-medium">User</th>
          <th class="px-4 py-3 font-medium">Action</th>
          <th class="px-4 py-3 font-medium">Details</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200">
        <?php if (empty($auditLogs)): ?>
          <tr>
            <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">No matching audit entries found.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($auditLogs as $log): ?>
            <tr class="bg-white">
              <td class="px-4 py-4 text-slate-600"><?= htmlspecialchars(date('M j, Y H:i', strtotime($log['created_at']))) ?></td>
              <td class="px-4 py-4 text-slate-700"><?= htmlspecialchars($log['user_name'] ?: 'System') ?></td>
              <td class="px-4 py-4 text-slate-700 font-medium"><?= htmlspecialchars($log['action']) ?></td>
              <td class="px-4 py-4 text-slate-600"><?= htmlspecialchars($log['details']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="mt-6">
    <?php renderPaginationControls('reports.php', $page, $perPage, $totalAudit); ?>
  </div>
</div>
<?php
renderAuthPageEnd();
