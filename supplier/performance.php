<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/ai.php';

requireRole('supplier');

$userId = $_SESSION['user_id'];
$user = currentUser();

// Get performance metrics
$metrics = getSupplierPerformanceMetrics($userId);
$recentActivity = getSupplierRecentActivity($userId, 20);
$trend = getSupplierTrend($userId);
?>
<?php renderAuthPageStart('Performance Dashboard', 'performance'); ?>

<div class="mb-8">
    <p class="text-slate-600">Track your bidding performance, win rate, and delivery reliability metrics.</p>
</div>

<!-- Key Performance Indicators -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white border border-slate-200 rounded-3xl p-6">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-slate-600 font-medium">Total Bids</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900"><?= $metrics['total_bids'] ?></p>
            </div>
            <span class="text-3xl">📊</span>
        </div>
        <p class="mt-4 text-xs text-slate-500">Procurements participated in</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-3xl p-6">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-slate-600 font-medium">Bids Won</p>
                <p class="mt-2 text-3xl font-semibold text-emerald-600"><?= $metrics['bids_won'] ?></p>
            </div>
            <span class="text-3xl">🏆</span>
        </div>
        <p class="mt-4 text-xs text-slate-500">Awarded contracts</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-3xl p-6">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-slate-600 font-medium">Win Rate</p>
                <p class="mt-2 text-3xl font-semibold text-blue-600"><?= round($metrics['win_rate'], 1) ?>%</p>
            </div>
            <span class="text-3xl">📈</span>
        </div>
        <p class="mt-4 text-xs text-slate-500">Success percentage</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-3xl p-6">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-slate-600 font-medium">Reliability Score</p>
                <p class="mt-2 text-3xl font-semibold text-amber-600"><?= ($metrics['reliability_score'] > 0 ? $metrics['reliability_score'] . '/100' : 'Not Yet Rated') ?></p>
            </div>
            <span class="text-3xl">⭐</span>
        </div>
        <p class="mt-4 text-xs text-slate-500">Based on performance history</p>
    </div>
</div>

<?php if ((int)$metrics['reliability_score'] === 0): ?>
<div class="mb-8 rounded-3xl border border-slate-200 bg-gradient-to-r from-slate-50 to-blue-50 p-6 shadow-sm">
    <div class="flex items-start gap-3">
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-white">ℹ️</span>
        <div>
            <h3 class="text-lg font-semibold text-slate-900">Not Yet Rated</h3>
            <p class="mt-1 text-sm text-slate-600">Complete procurements and build delivery history to unlock your real reliability score.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Detailed Metrics Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white border border-slate-200 rounded-3xl p-6">
        <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-widest mb-4">Bid Statistics</h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-slate-600">Total Bids Submitted:</span>
                <span class="font-semibold text-slate-900"><?= $metrics['total_bids'] ?></span>
            </div>
            <div class="border-t border-slate-200 pt-3 flex justify-between items-center">
                <span class="text-emerald-600">Bids Won:</span>
                <span class="font-semibold text-emerald-600"><?= $metrics['bids_won'] ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-rose-600">Bids Lost:</span>
                <span class="font-semibold text-rose-600"><?= $metrics['bids_lost'] ?></span>
            </div>
            <div class="border-t border-slate-200 pt-3">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-slate-600 text-sm">Win Rate</span>
                    <span class="font-semibold text-slate-900"><?= round($metrics['win_rate'], 1) ?>%</span>
                </div>
                <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500" style="width: <?= min(100, $metrics['win_rate']) ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-3xl p-6">
        <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-widest mb-4">Delivery Performance</h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-slate-600">On-Time Deliveries:</span>
                <span class="font-semibold text-slate-900"><?= $metrics['on_time_deliveries'] ?></span>
            </div>
            <div class="border-t border-slate-200 pt-3">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-slate-600 text-sm">On-Time Rate</span>
                    <span class="font-semibold text-slate-900"><?= round($metrics['on_time_rate'], 1) ?>%</span>
                </div>
                <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500" style="width: <?= min(100, $metrics['on_time_rate']) ?>%"></div>
                </div>
            </div>
            <div class="pt-2 text-center">
                <p class="text-xs text-slate-500">Rating: <span class="font-semibold text-slate-900"><?= round($metrics['delivery_rating'], 2) ?>/5</span></p>
            </div>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-3xl p-6">
        <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-widest mb-4">Reliability Score</h3>
        <div class="flex flex-col items-center justify-center">
            <div class="relative w-24 h-24 mb-4">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="45" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                    <circle cx="50" cy="50" r="45" fill="none" stroke="#1f2937" stroke-width="8" stroke-dasharray="<?= ($metrics['reliability_score'] > 0 ? ($metrics['reliability_score'] / 100) * 282.7 : 0) ?> 282.7" stroke-linecap="round"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-xl font-bold text-slate-900"><?= $metrics['reliability_score'] > 0 ? $metrics['reliability_score'] : '—' ?></span>
                </div>
            </div>
            <p class="text-sm text-slate-600 text-center">
                <?php
                $score = $metrics['reliability_score'];
                if ($score >= 80) {
                    echo 'Excellent reliability';
                } elseif ($score >= 60) {
                    echo 'Good reliability';
                } elseif ($score >= 40) {
                    echo 'Fair reliability';
                } else {
                    echo 'Needs improvement';
                }
                ?>
            </p>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="bg-white border border-slate-200 rounded-3xl p-8">
    <h3 class="text-lg font-semibold text-slate-900 mb-6">Recent Procurement Participation</h3>
    <?php if (empty($recentActivity)): ?>
        <div class="text-center py-12">
            <p class="text-slate-600">No bid history yet. Start by browsing opportunities.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Procurement</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Your Price</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Delivery</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Status</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Submitted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php foreach ($recentActivity as $activity): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-900"><?= htmlspecialchars($activity['title']) ?></td>
                        <td class="px-4 py-3 text-slate-600">$<?= number_format($activity['price'], 2) ?></td>
                        <td class="px-4 py-3 text-slate-600"><?= $activity['delivery_days'] ?> days</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-medium
                                <?php
                                if ($activity['status'] === 'awarded') {
                                    echo 'bg-emerald-100 text-emerald-900';
                                } elseif ($activity['status'] === 'rejected') {
                                    echo 'bg-rose-100 text-rose-900';
                                } else {
                                    echo 'bg-slate-100 text-slate-900';
                                }
                                ?>
                            ">
                                <?= htmlspecialchars($activity['status']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs"><?= date('M j, Y', strtotime($activity['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php renderAuthPageEnd(); ?>
