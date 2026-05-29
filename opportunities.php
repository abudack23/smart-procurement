<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';
requireRole('supplier');
$user = currentUser();

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'deadline';
$pg = getPaginationParams(10);
$page = $pg['page'];
$perPage = $pg['per_page'];
$offset = $pg['offset'];

$where = 'WHERE p.status = "open"';
$params = [];
if ($search !== '') {
    $where .= ' AND (p.title LIKE ? OR p.description LIKE ? OR p.evaluation_criteria LIKE ? OR p.budget LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}
$orderBy = 'p.submission_deadline ASC';
if ($sort === 'newest') {
    $orderBy = 'p.created_at DESC';
} elseif ($sort === 'oldest') {
    $orderBy = 'p.created_at ASC';
} elseif ($sort === 'budget_desc') {
    $orderBy = 'CAST(p.budget AS DECIMAL(12,2)) DESC';
} elseif ($sort === 'budget_asc') {
    $orderBy = 'CAST(p.budget AS DECIMAL(12,2)) ASC';
}

$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM procurements p ' . $where);
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$query = 'SELECT p.*, COUNT(b.id) AS bid_count FROM procurements p LEFT JOIN bids b ON b.procurement_id = p.id ' . $where . ' GROUP BY p.id ORDER BY ' . $orderBy . ' LIMIT ? OFFSET ?';
$stmt = $pdo->prepare($query);
$bindIndex = 1;
foreach ($params as $param) {
    $stmt->bindValue($bindIndex++, $param, PDO::PARAM_STR);
}
$stmt->bindValue($bindIndex++, (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue($bindIndex++, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$openProcurements = $stmt->fetchAll();

$myBids = getSupplierBids($user['id']);
$bidMap = [];
foreach ($myBids as $bid) {
    $bidMap[$bid['procurement_id']] = $bid;
}

renderAuthPageStart('Opportunities', 'opportunities');
?>
<div class="grid gap-6 xl:grid-cols-3">
  <section class="xl:col-span-2 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="text-xl font-semibold text-slate-900">Open opportunities</h2>
        <p class="text-sm text-slate-500">Browse current procurements and submit bids.</p>
      </div>
      <form method="get" class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <label class="sr-only" for="search">Search procurements</label>
        <input id="search" name="search" type="text" value="<?= htmlspecialchars($search) ?>" placeholder="Search procurements, budget or keywords" class="w-full min-w-[220px] rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
        <label class="sr-only" for="sort">Sort procurements</label>
        <select id="sort" name="sort" class="rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200">
          <option value="deadline" <?= $sort === 'deadline' ? 'selected' : '' ?>>Deadline</option>
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
          <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
          <option value="budget_desc" <?= $sort === 'budget_desc' ? 'selected' : '' ?>>Budget high-low</option>
          <option value="budget_asc" <?= $sort === 'budget_asc' ? 'selected' : '' ?>>Budget low-high</option>
        </select>
        <button type="submit" class="rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
      </form>
    </div>
    <div class="space-y-4">
      <?php if (empty($openProcurements)): ?>
        <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">There are no open opportunities right now.</div>
      <?php else: ?>
        <?php foreach ($openProcurements as $procurement): ?>
          <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-slate-300 hover:shadow-lg">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-200">Open</span>
                  <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"><?= (int)($procurement['bid_count'] ?? 0) ?> competing bids</span>
                  <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">AI assisted</span>
                </div>
                <h3 class="mt-3 text-xl font-semibold text-slate-900"><?= htmlspecialchars($procurement['title']) ?></h3>
                <p class="mt-2 text-sm leading-6 text-slate-600"><?= htmlspecialchars($procurement['description']) ?></p>
              </div>
              <div class="rounded-3xl bg-slate-50 p-4 text-sm text-slate-600 shadow-inner">
                <p class="font-semibold text-slate-900">Budget: <?= htmlspecialchars($procurement['budget'] ?: 'TBD') ?></p>
                <p class="mt-2">Deadline: <?= date('M j, Y', strtotime($procurement['submission_deadline'])) ?></p>
                <p class="mt-2">Delivery target: <?= (int)($procurement['delivery_days'] ?? 0) ?> days</p>
              </div>
            </div>
            <div class="mt-5 flex flex-wrap items-center gap-3">
              <?php if (isset($bidMap[$procurement['id']])): ?>
                <span class="inline-flex min-w-[150px] items-center justify-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Bid submitted: <?= ucfirst($bidMap[$procurement['id']]['status']) ?></span>
                <a href="submit_bid.php?procurement_id=<?= $procurement['id'] ?>" class="inline-flex min-w-[150px] items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-100">Update bid</a>
              <?php else: ?>
                <a href="submit_bid.php?procurement_id=<?= $procurement['id'] ?>" class="inline-flex min-w-[150px] items-center justify-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Submit bid</a>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php renderPaginationControls('opportunities.php', $page, $perPage, $total); ?>
  </section>
  <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-6">
      <h2 class="text-xl font-semibold text-slate-900">Your bids</h2>
      <p class="text-sm text-slate-500">Track your submitted proposals and awards.</p>
    </div>
    <div class="space-y-4">
      <?php if (empty($myBids)): ?>
        <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">You haven’t submitted any bids yet.</div>
      <?php else: ?>
        <?php foreach ($myBids as $bid): ?>
          <article class="rounded-3xl border border-slate-200 bg-slate-50 p-4 shadow-sm transition duration-150 hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($bid['title']) ?></p>
                <p class="mt-1 text-xs text-slate-500">Price: ₱<?= number_format($bid['price'], 2) ?> • Delivery: <?= htmlspecialchars($bid['delivery_days']) ?> days</p>
              </div>
              <span class="<?= getStatusBadgeClass($bid['status']) ?>"><?= ucfirst($bid['status']) ?></span>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
</div>
<?php
renderAuthPageEnd();
