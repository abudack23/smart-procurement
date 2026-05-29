<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';
requireLogin();
$user = currentUser();

if (in_array($user['role'], ['admin', 'superadmin'], true)) {
    $totalProcurements = countProcurements();
    $totalSuppliers = countBidders();
    $ongoingBids = countOngoingBids();
    $completedBids = countCompletedBids();
    $totalBudget = getTotalProcurementBudget();
    $topSuppliers = getSupplierRankings(5);
    $recentProcurements = getRecentProcurements();
} else {
    $stats = getSupplierPerformanceMetrics($user['id']);
    $openProcurements = getOpenProcurements();
    $myBids = getSupplierBids($user['id']);
}

renderAuthPageStart('Dashboard', 'dashboard');
?>
<div class="grid gap-6 xl:grid-cols-4">
  <?php if (in_array($user['role'], ['admin', 'superadmin'], true)): ?>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <p class="text-sm font-medium text-slate-500">Procurements</p>
      <p class="mt-4 text-3xl font-semibold text-slate-900"><?= $totalProcurements ?></p>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <p class="text-sm font-medium text-slate-500">Suppliers</p>
      <p class="mt-4 text-3xl font-semibold text-slate-900"><?= $totalSuppliers ?></p>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <p class="text-sm font-medium text-slate-500">Pending bids</p>
      <p class="mt-4 text-3xl font-semibold text-slate-900"><?= $ongoingBids ?></p>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <p class="text-sm font-medium text-slate-500">Total budget</p>
      <p class="mt-4 text-3xl font-semibold text-slate-900">₱<?= number_format($totalBudget, 2) ?></p>
    </div>
  <?php else: ?>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <p class="text-sm font-medium text-slate-500">Total bids submitted</p>
      <p class="mt-4 text-3xl font-semibold text-slate-900"><?= $stats['total_bids'] ?? 0 ?></p>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <p class="text-sm font-medium text-slate-500">Awarded bids</p>
      <p class="mt-4 text-3xl font-semibold text-slate-900"><?= $stats['bids_won'] ?? 0 ?></p>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <p class="text-sm font-medium text-slate-500">Win rate</p>
      <p class="mt-4 text-3xl font-semibold text-slate-900"><?= round($stats['win_rate'], 1) ?>%</p>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <p class="text-sm font-medium text-slate-500">Reliability score</p>
      <p class="mt-4 text-3xl font-semibold text-slate-900"><?= ($stats['reliability_score'] > 0 ? $stats['reliability_score'] . '/100' : 'Not Yet Rated') ?></p>
    </div>
  <?php endif; ?>
</div>

<?php if (in_array($user['role'], ['admin', 'superadmin'], true)): ?>
  <section class="mt-10 grid gap-6 xl:grid-cols-[1.4fr_0.6fr]">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-xl font-semibold text-slate-900">Procurement pipeline</h2>
          <p class="text-sm text-slate-500">Recent procurement activity and incoming bids.</p>
        </div>
        <a href="procurements.php" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Manage procurements</a>
      </div>
      <?php if (empty($recentProcurements)): ?>
        <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">No procurements have been created yet.</div>
      <?php else: ?>
        <div class="space-y-4">
          <?php foreach ($recentProcurements as $procurement): ?>
            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <h3 class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($procurement['title']) ?></h3>
                  <p class="mt-2 text-sm text-slate-500">Deadline: <?= date('M j, Y', strtotime($procurement['submission_deadline'])) ?> • Status: <?= ucfirst($procurement['status']) ?></p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">Budget: <?= htmlspecialchars($procurement['budget'] ?: 'TBD') ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
          <h2 class="text-xl font-semibold text-slate-900">Top supplier rankings</h2>
          <p class="text-sm text-slate-500">Performance analytics for your top suppliers and contract winners.</p>
        </div>
        <a href="users.php" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Manage suppliers</a>
      </div>
      <?php if (empty($topSuppliers)): ?>
        <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">No supplier ranking data available.</div>
      <?php else: ?>
        <div class="space-y-4">
          <?php $topSupplier = $topSuppliers[0]; ?>
          <div class="rounded-[2rem] border border-slate-200 bg-gradient-to-br from-emerald-50 via-white to-slate-50 p-5 shadow-sm">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
              <div class="min-w-0">
                <p class="text-sm uppercase tracking-[0.24em] text-slate-500">Top ranked supplier</p>
                <h3 class="mt-3 text-2xl font-semibold text-slate-900"><?= htmlspecialchars($topSupplier['name']) ?></h3>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">This supplier leads the current ranking with strong delivery consistency and high win rates across awarded contracts.</p>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center justify-center rounded-full bg-emerald-100 px-4 py-2 text-sm font-semibold text-emerald-700">Top Performer</span>
                <span class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700"><?= ($topSupplier['reliability_score'] > 0 ? 'Reliability ' . $topSupplier['reliability_score'] . '%' : 'Not Yet Rated') ?></span>
              </div>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-3">
              <div class="rounded-3xl bg-white p-4 shadow-sm">
                <p class="text-sm text-slate-500">Win rate</p>
                <p class="mt-3 text-2xl font-semibold text-slate-900"><?= round($topSupplier['win_rate'], 1) ?>%</p>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                  <div class="h-full rounded-full bg-emerald-500" style="width: <?= min(100, max(0, round($topSupplier['win_rate'], 1))) ?>%;"></div>
                </div>
              </div>
              <div class="rounded-3xl bg-white p-4 shadow-sm">
                <p class="text-sm text-slate-500">Awards won</p>
                <p class="mt-3 text-2xl font-semibold text-slate-900"><?= $topSupplier['bids_won'] ?? 0 ?></p>
                <p class="mt-2 text-sm text-slate-500">Contracts awarded to date.</p>
              </div>
              <div class="rounded-3xl bg-white p-4 shadow-sm">
                <p class="text-sm text-slate-500">On-time delivery</p>
                <p class="mt-3 text-2xl font-semibold text-slate-900"><?= round($topSupplier['on_time_rate'], 1) ?>%</p>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                  <div class="h-full rounded-full bg-emerald-500" style="width: <?= min(100, max(0, round($topSupplier['on_time_rate'], 1))) ?>%;"></div>
                </div>
              </div>
            </div>
          </div>
          <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
              <div>
                <p class="text-sm font-semibold text-slate-900">Supplier performance chart</p>
                <p class="text-sm text-slate-500">Compare reliability and win rate across the top suppliers.</p>
              </div>
            </div>
            <div class="space-y-3">
              <?php foreach ($topSuppliers as $index => $supplier):
                $rank = $index + 1;
                $reliability = min(100, max(0, $supplier['reliability_score']));
                $winRate = min(100, max(0, round($supplier['win_rate'], 1)));
                $reliabilityColor = $reliability >= 80 ? 'bg-emerald-500' : ($reliability >= 60 ? 'bg-amber-500' : 'bg-rose-500');
                $winColor = $winRate >= 80 ? 'bg-emerald-500' : ($winRate >= 50 ? 'bg-amber-500' : 'bg-rose-500');
                $healthLabel = $reliability >= 80 ? 'High' : ($reliability >= 60 ? 'Medium' : 'Low');
              ?>
                <div class="rounded-3xl border border-slate-200 bg-white p-4 transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                      <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-sm font-semibold text-white">#<?= $rank ?></div>
                      <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900"><?= htmlspecialchars($supplier['name']) ?></p>
                        <p class="mt-1 text-sm text-slate-500"><?= round($supplier['win_rate'], 1) ?>% win rate • <?= $supplier['reliability_score'] ?>/100 reliability</p>
                      </div>
                    </div>
                    <span class="inline-flex h-9 items-center justify-center rounded-full border px-3 text-sm font-semibold text-slate-900 <?= $reliabilityColor ?>/10"><?= $healthLabel ?></span>
                  </div>
                  <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="space-y-2">
                      <div class="flex items-center justify-between text-sm text-slate-500">
                        <span>Reliability</span>
                        <span class="font-semibold text-slate-900"><?= $reliability ?>%</span>
                      </div>
                      <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full rounded-full <?= $reliabilityColor ?>" style="width: <?= $reliability ?>%;"></div>
                      </div>
                    </div>
                    <div class="space-y-2">
                      <div class="flex items-center justify-between text-sm text-slate-500">
                        <span>Win rate</span>
                        <span class="font-semibold text-slate-900"><?= $winRate ?>%</span>
                      </div>
                      <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full rounded-full <?= $winColor ?>" style="width: <?= $winRate ?>%;"></div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>
<?php else: ?>
  <section class="mt-10 grid gap-6 xl:grid-cols-2">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex items-center justify-between gap-4 mb-4">
        <h2 class="text-xl font-semibold text-slate-900">Open opportunities</h2>
        <a href="opportunities.php" class="text-sm font-semibold text-slate-900 hover:underline">View all</a>
      </div>
      <?php if (empty($openProcurements)): ?>
        <p class="text-sm text-slate-500">There are no open bids right now.</p>
      <?php else: ?>
        <ul class="space-y-4">
          <?php foreach (array_slice($openProcurements, 0, 4) as $procurement): ?>
            <li class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
              <h3 class="text-base font-semibold text-slate-900"><?= htmlspecialchars($procurement['title']) ?></h3>
              <p class="mt-2 text-sm text-slate-500">Deadline: <?= date('M j, Y', strtotime($procurement['submission_deadline'])) ?> | Budget: <?= htmlspecialchars($procurement['budget'] ?: 'TBD') ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex items-center justify-between gap-4 mb-4">
        <h2 class="text-xl font-semibold text-slate-900">My recent bids</h2>
        <a href="my_bids.php" class="text-sm font-semibold text-slate-900 hover:underline">View all</a>
      </div>
      <?php if (empty($myBids)): ?>
        <p class="text-sm text-slate-500">You haven’t placed any bids yet.</p>
      <?php else: ?>
        <div class="space-y-4">
          <?php foreach (array_slice($myBids, 0, 4) as $bid): ?>
            <article class="rounded-3xl border border-slate-200 bg-slate-50 p-4 shadow-sm transition duration-150 hover:-translate-y-0.5 hover:shadow-md">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($bid['title']) ?></p>
                  <p class="mt-2 text-xs text-slate-500">Submitted <?= date('M j, Y', strtotime($bid['created_at'])) ?></p>
                </div>
                <span class="<?= getStatusBadgeClass($bid['status']) ?>"><?= ucfirst($bid['status']) ?></span>
              </div>
              <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-slate-600">
                <span class="rounded-full bg-white px-3 py-1 shadow-sm">₱<?= number_format($bid['price'], 2) ?></span>
                <span class="rounded-full bg-white px-3 py-1 shadow-sm"><?= (int)$bid['delivery_days'] ?> days</span>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>
<?php
renderAuthPageEnd();
