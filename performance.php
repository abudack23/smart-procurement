<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/ai.php';
requireRole('supplier');

$userId = $_SESSION['user_id'];
$user = currentUser();
$metrics = getSupplierPerformanceMetrics($userId);
$recentActivity = getSupplierRecentActivity($userId, 20);
$trend = getSupplierTrend($userId);

renderAuthPageStart('Performance Dashboard', 'performance');
?>
<div class="mb-8">
  <p class="text-slate-600">Monitor your supplier performance metrics, reliability, and bid success trends in real time.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
  <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-sm font-medium text-slate-500">Total bids submitted</p>
    <p class="mt-4 text-3xl font-semibold text-slate-900"><?= $metrics['total_bids'] ?></p>
  </div>
  <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-sm font-medium text-slate-500">Bids won</p>
    <p class="mt-4 text-3xl font-semibold text-emerald-600"><?= $metrics['bids_won'] ?></p>
  </div>
  <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-sm font-medium text-slate-500">Win rate</p>
    <p class="mt-4 text-3xl font-semibold text-blue-600"><?= $metrics['win_rate'] ?>%</p>
  </div>
  <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-sm font-medium text-slate-500">Reliability score</p>
    <p class="mt-4 text-3xl font-semibold text-amber-600"><?= ($metrics['reliability_score'] > 0 ? $metrics['reliability_score'] . '/100' : 'Not Yet Rated') ?></p>
  </div>
</div>

<?php if ((int)$metrics['reliability_score'] === 0): ?>
  <div class="mb-8 rounded-3xl border border-slate-200 bg-gradient-to-r from-slate-50 to-blue-50 p-6 shadow-sm">
    <div class="flex items-start gap-3">
      <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-white">ℹ️</span>
      <div>
        <h3 class="text-lg font-semibold text-slate-900">Not Yet Rated</h3>
        <p class="mt-1 text-sm text-slate-600">Complete procurements and win/ delivery history to build your reliability score.</p>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
  <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="text-lg font-semibold text-slate-900 mb-4">Delivery performance</h3>
    <p class="text-sm text-slate-500 mb-4">On-time delivery performance based on your bid history.</p>
    <div class="space-y-3 text-sm text-slate-600">
      <div class="flex justify-between">
        <span>Deliveries on time</span>
        <span class="font-semibold text-slate-900"><?= $metrics['on_time_deliveries'] ?></span>
      </div>
      <div class="flex justify-between">
        <span>On-time rate</span>
        <span class="font-semibold text-slate-900"><?= $metrics['on_time_rate'] ?>%</span>
      </div>
      <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
        <div class="h-full bg-blue-500" style="width: <?= min(100, $metrics['on_time_rate']) ?>%"></div>
      </div>
    </div>
  </div>
  <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="text-lg font-semibold text-slate-900 mb-4">Win rate trend</h3>
    <p class="text-sm text-slate-500 mb-4">A quick view of your most recent procurement outcomes.</p>
    <div class="space-y-3 text-sm text-slate-600">
      <?php if (empty($trend['labels'])): ?>
        <p>No recent activity to display.</p>
      <?php else: ?>
        <?php foreach ($trend['labels'] as $index => $label): ?>
          <div class="flex items-center justify-between">
            <span><?= htmlspecialchars($label) ?></span>
            <span class="font-semibold text-slate-900"><?= $trend['awarded'][$index] ? 'Win' : ($trend['rejected'][$index] ? 'Lose' : 'Pending') ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="text-lg font-semibold text-slate-900 mb-4">Trend summary</h3>
    <div class="space-y-3 text-sm text-slate-600">
      <div class="flex justify-between">
        <span>Recent awards</span>
        <span class="font-semibold text-emerald-700"><?= array_sum($trend['awarded']) ?></span>
      </div>
      <div class="flex justify-between">
        <span>Recent rejections</span>
        <span class="font-semibold text-rose-700"><?= array_sum($trend['rejected']) ?></span>
      </div>
      <div class="flex justify-between">
        <span>Recent on-time</span>
        <span class="font-semibold text-blue-700"><?= array_sum($trend['on_time']) ?></span>
      </div>
    </div>
  </div>
</div>

<div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
  <div class="flex items-center justify-between gap-4 mb-6">
    <div>
      <h3 class="text-xl font-semibold text-slate-900">Recent participation</h3>
      <p class="text-sm text-slate-500">Your last 20 bids and outcomes.</p>
    </div>
    <a href="my_bids.php" class="text-sm font-semibold text-slate-900 hover:underline">See all bids</a>
  </div>
  <?php if (empty($recentActivity)): ?>
    <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">You have no recent bid activity.</div>
  <?php else: ?>
    <div class="space-y-4">
      <?php foreach ($recentActivity as $activity): ?>
        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
          <div class="flex items-center justify-between gap-4">
            <div>
              <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($activity['title']) ?></p>
              <p class="mt-1 text-sm text-slate-500">Price: ₱<?= number_format($activity['price'], 2) ?> • Delivery: <?= htmlspecialchars($activity['delivery_days']) ?> days</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"><?= ucfirst($activity['status']) ?></span>
          </div>
          <p class="mt-3 text-sm text-slate-600">Submitted: <?= date('M j, Y', strtotime($activity['created_at'])) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php renderAuthPageEnd();
