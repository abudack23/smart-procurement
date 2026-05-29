<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';
requireRole('admin');

$procurementId = (int)($_GET['procurement_id'] ?? 0);
$procurement = getProcurementById($procurementId);
if (!$procurement) {
    flash('Procurement not found.', 'error');
    header('Location: procurements.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['award_bid'])) {
    $bidId = (int)($_POST['bid_id'] ?? 0);
    if (!$bidId) {
        flash('Invalid bid.', 'error');
    } else {
        $stmt = $pdo->prepare('UPDATE bids SET status = "awarded" WHERE id = ?');
        $stmt->execute([$bidId]);
        $stmt = $pdo->prepare('UPDATE procurements SET status = "closed" WHERE id = ?');
        $stmt->execute([$procurementId]);
        $stmt = $pdo->prepare('UPDATE bids SET status = "rejected" WHERE procurement_id = ? AND id != ?');
        $stmt->execute([$procurementId, $bidId]);
        $winning = $pdo->prepare('SELECT supplier_id FROM bids WHERE id = ?');
        $winning->execute([$bidId]);
        $winner = $winning->fetch();
        if ($winner && $winner['supplier_id']) {
            notifySupplier($winner['supplier_id'], 'Bid Awarded', "Congratulations! Your bid for '{$procurement['title']}' has been awarded.");
        }
        $losers = $pdo->prepare('SELECT supplier_id FROM bids WHERE procurement_id = ? AND id != ?');
        $losers->execute([$procurementId, $bidId]);
        while ($row = $losers->fetch()) {
            notifySupplier($row['supplier_id'], 'Bid Result', "Your bid for '{$procurement['title']}' was not selected. Thank you for participating.");
        }
        logAction($_SESSION['user_id'], 'Awarded bid', 'Bid ID: ' . $bidId . ' Procurement ID: ' . $procurementId);
        flash('Bid awarded successfully.');
        header('Location: view_bids.php?procurement_id=' . $procurementId);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_bid'])) {
    $bidId = (int)($_POST['bid_id'] ?? 0);
    if ($bidId) {
        $stmt = $pdo->prepare('UPDATE bids SET status = "rejected" WHERE id = ?');
        $stmt->execute([$bidId]);
        $bidUser = $pdo->prepare('SELECT supplier_id FROM bids WHERE id = ?');
        $bidUser->execute([$bidId]);
        $row = $bidUser->fetch();
        if ($row && $row['supplier_id']) {
            notifySupplier($row['supplier_id'], 'Bid rejected', "Your bid for '{$procurement['title']}' has been rejected.");
        }
        logAction($_SESSION['user_id'], 'Rejected bid', 'Bid ID: ' . $bidId . ' Procurement ID: ' . $procurementId);
        flash('Bid rejected successfully.');
    }
    header('Location: view_bids.php?procurement_id=' . $procurementId);
    exit;
}

require_once __DIR__ . '/includes/ai.php';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$pg = getPaginationParams(10);
$page = $pg['page'];
$perPage = $pg['per_page'];
$offset = $pg['offset'];

$where = 'WHERE b.procurement_id = ?';
$params = [$procurementId];
if ($search !== '') {
    $where .= ' AND (u.name LIKE ? OR b.status LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}
$orderBy = 'b.created_at DESC';
if ($sort === 'oldest') {
    $orderBy = 'b.created_at ASC';
} elseif ($sort === 'status') {
    $orderBy = 'b.status ASC';
}

$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM bids b JOIN users u ON u.id = b.supplier_id ' . $where);
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$query = 'SELECT b.*, u.name AS supplier_name FROM bids b JOIN users u ON u.id = b.supplier_id ' . $where . ' ORDER BY ' . $orderBy . ' LIMIT ? OFFSET ?';
$stmt = $pdo->prepare($query);
$bindIndex = 1;
foreach ($params as $param) {
    $stmt->bindValue($bindIndex++, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue($bindIndex++, (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue($bindIndex++, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$bids = $stmt->fetchAll();

renderAuthPageStart('Bids for: ' . $procurement['title'], 'procurements');
?>
<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
  <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between mb-6">
    <div>
      <h2 class="text-xl font-semibold text-slate-900">Bids for: <?= htmlspecialchars($procurement['title']) ?></h2>
      <p class="text-sm text-slate-500">Review submitted bids and award a winner.</p>
    </div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
      <form method="get" class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <input type="hidden" name="procurement_id" value="<?= $procurementId ?>" />
        <label class="sr-only" for="search">Search bids</label>
        <input id="search" name="search" type="text" value="<?= htmlspecialchars($search) ?>" placeholder="Search supplier or status" class="min-w-[220px] rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
        <label class="sr-only" for="sort">Sort</label>
        <select id="sort" name="sort" class="rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
          <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
          <option value="status" <?= $sort === 'status' ? 'selected' : '' ?>>Status</option>
        </select>
        <button type="submit" class="rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
      </form>
      <a href="procurements.php" class="text-sm font-semibold text-slate-900 hover:underline">Back to procurements</a>
    </div>
  </div>
  <?php if (empty($bids)): ?>
    <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">No bids submitted yet for this procurement.</div>
  <?php else: ?>
    <div class="space-y-4">
      <?php foreach ($bids as $bid): ?>
        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($bid['supplier_name']) ?></p>
              <p class="mt-1 text-xs text-slate-500">Price: ₱<?= number_format($bid['price'], 2) ?> • Delivery: <?= htmlspecialchars($bid['delivery_days']) ?> days</p>
            </div>
            <?php
              $bidStatus = $bid['status'];
              $statusClasses = $bidStatus === 'awarded'
                ? 'bg-emerald-100 text-emerald-700'
                : ($bidStatus === 'rejected'
                  ? 'bg-rose-100 text-rose-700'
                  : 'bg-slate-100 text-slate-700');
            ?>
            <div class="text-right">
              <p class="text-sm font-medium text-slate-900"><?= ucfirst($bidStatus) ?></p>
              <div class="mt-3 flex flex-wrap justify-end gap-2">
                <span class="inline-flex items-center justify-center rounded-full px-4 py-2 text-sm font-semibold <?= $statusClasses ?>"><?= ucfirst($bidStatus) ?></span>
                <?php if (!in_array($bidStatus, ['awarded', 'rejected'], true)): ?>
                  <form method="post" class="inline-flex">
                    <input type="hidden" name="bid_id" value="<?= $bid['id'] ?>" />
                    <button type="submit" name="award_bid" class="inline-flex min-w-[110px] items-center justify-center rounded-full bg-emerald-500 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600">Award</button>
                  </form>
                  <form method="post" class="inline-flex">
                    <input type="hidden" name="bid_id" value="<?= $bid['id'] ?>" />
                    <button type="submit" name="reject_bid" class="inline-flex min-w-[110px] items-center justify-center rounded-full bg-rose-500 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-600">Reject</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php if ($bid['remarks']): ?>
            <p class="mt-3 text-sm text-slate-600">Remarks: <?= nl2br(htmlspecialchars($bid['remarks'])) ?></p>
          <?php endif; ?>
          <?php if ($bid['proposal_document']): ?>
            <p class="mt-3 text-sm"><a href="<?= htmlspecialchars($bid['proposal_document']) ?>" class="text-sm font-semibold text-slate-900 hover:underline">Download proposal</a></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php renderPaginationControls('view_bids.php?procurement_id=' . $procurementId, $page, $perPage, $total); ?>
  <?php endif; ?>
</div>
<?php
renderAuthPageEnd();
