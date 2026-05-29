<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';
requireRole('supplier');
$user = currentUser();
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$pg = getPaginationParams(10);
$page = $pg['page'];
$perPage = $pg['per_page'];
$offset = $pg['offset'];

$where = 'WHERE b.supplier_id = ?';
$params = [$user['id']];
if ($search !== '') {
    $where .= ' AND (p.title LIKE ? OR b.status LIKE ?)';
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

$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM bids b JOIN procurements p ON p.id = b.procurement_id ' . $where);
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$stmt = $pdo->prepare('SELECT b.*, p.title, p.budget FROM bids b JOIN procurements p ON p.id = b.procurement_id ' . $where . ' ORDER BY ' . $orderBy . ' LIMIT ? OFFSET ?');
$bindIndex = 1;
foreach ($params as $param) {
    $stmt->bindValue($bindIndex++, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue($bindIndex++, (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue($bindIndex++, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$bids = $stmt->fetchAll();

renderAuthPageStart('My Bids', 'my_bids');
?>
<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between mb-6">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">My Bids</h2>
            <p class="text-sm text-slate-500">Track the status of your active and completed bids.</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label class="sr-only" for="search">Search bids</label>
            <input id="search" name="search" type="text" value="<?= htmlspecialchars($search) ?>" placeholder="Search bids or status" class="min-w-[220px] rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" form="filterForm" />
            <label class="sr-only" for="sort">Sort bids</label>
            <select id="sort" name="sort" class="rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" form="filterForm">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                <option value="status" <?= $sort === 'status' ? 'selected' : '' ?>>Status</option>
            </select>
            <button type="submit" form="filterForm" class="rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
            <a href="opportunities.php" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Browse opportunities</a>
        </div>
    </div>
    <form method="get" id="filterForm"></form>
    <?php if (empty($bids)): ?>
        <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">You haven’t submitted any bids yet.</div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($bids as $bid): ?>
                <article class="rounded-3xl border border-slate-200 bg-slate-50 p-5 shadow-sm transition duration-150 hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-base font-semibold text-slate-900"><?= htmlspecialchars($bid['title']) ?></p>
                            <p class="mt-2 text-sm text-slate-500">Price: ₱<?= number_format($bid['price'], 2) ?> • Delivery: <?= htmlspecialchars($bid['delivery_days']) ?> days</p>
                            <p class="mt-1 text-sm text-slate-500">Budget context: <?= htmlspecialchars($bid['budget'] ?: 'TBD') ?> • Submitted: <?= date('M j, Y', strtotime($bid['created_at'])) ?></p>
                        </div>
                        <div class="flex flex-col gap-2 items-start sm:items-end">
                            <span class="<?= getStatusBadgeClass($bid['status']) ?>"><?= ucfirst($bid['status']) ?></span>
                            <?php if ($bid['status'] === 'pending'): ?>
                                <a href="submit_bid.php?procurement_id=<?= $bid['procurement_id'] ?>" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-900 hover:bg-slate-100">Edit bid</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold text-slate-700">
                        <span class="rounded-full bg-white px-3 py-1 shadow-sm"><?= (float)$bid['price'] <= (float)($bid['budget'] ?: 0) ? 'Competitive pricing' : 'Needs review' ?></span>
                        <span class="rounded-full bg-indigo-100 px-3 py-1 text-indigo-700 shadow-sm">AI assistance enabled</span>
                    </div>
                    <?php if ($bid['remarks']): ?>
                        <p class="mt-4 text-sm text-slate-600">Remarks: <?= nl2br(htmlspecialchars($bid['remarks'])) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <?php renderPaginationControls('my_bids.php', $page, $perPage, $total); ?>
    <?php endif; ?>
</div>
<?php renderAuthPageEnd();
