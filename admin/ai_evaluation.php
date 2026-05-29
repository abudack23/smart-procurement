<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('admin');

$selectedProcurementId = $_GET['procurement_id'] ?? null;
$selectedProcurement = null;
$rankedBids = [];
$recommendations = [];

if ($selectedProcurementId) {
    $selectedProcurement = getProcurementById($selectedProcurementId);
    if ($selectedProcurement) {
        $rankedBids = getRankedBidsForProcurement($selectedProcurementId);
        if (!empty($rankedBids)) {
            $recommendations = [
                'best_overall' => $rankedBids[0],
                'lowest_cost' => array_reduce($rankedBids, function($carry, $bid) {
                    return $carry === null || $bid['price'] < $carry['price'] ? $bid : $carry;
                }, null),
                'fastest_delivery' => array_reduce($rankedBids, function($carry, $bid) {
                    return $carry === null || $bid['delivery_days'] < $carry['delivery_days'] ? $bid : $carry;
                }, null),
                'most_reliable' => array_reduce($rankedBids, function($carry, $bid) {
                    return $carry === null || $bid['reliability_score'] > $carry['reliability_score'] ? $bid : $carry;
                }, null),
            ];
        }
    }
}

$procurements = getRecentProcurements(50);
?>
<?php renderAuthPageStart('AI Evaluation', 'ai_evaluation'); ?>

<div class="mb-8">
    <p class="text-slate-600 mb-6">Use AI-powered bid analysis to rank suppliers and make informed procurement decisions.</p>
</div>

<!-- Procurement Selection -->
<div class="bg-white border border-slate-200 rounded-3xl p-8 mb-8">
    <h2 class="text-lg font-semibold text-slate-900 mb-4">Select a Procurement</h2>
    <form method="get" class="space-y-4">
        <div>
            <label for="procurement_id" class="block text-sm font-medium text-slate-700 mb-2">Procurement</label>
            <select name="procurement_id" id="procurement_id" class="w-full px-4 py-2 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-slate-900" onchange="this.form.submit()">
                <option value="">-- Choose a procurement --</option>
                <?php foreach ($procurements as $p): ?>
                    <option value="<?= htmlspecialchars($p['id']) ?>" <?= $p['id'] == $selectedProcurementId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['title']) ?> (<?= $p['status'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($selectedProcurement): ?>
    <!-- Procurement Details -->
    <div class="bg-white border border-slate-200 rounded-3xl p-8 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="border-r border-slate-200 pr-6">
                <p class="text-sm text-slate-600 font-medium">Title</p>
                <p class="mt-1 text-lg font-semibold text-slate-900"><?= htmlspecialchars($selectedProcurement['title']) ?></p>
            </div>
            <div class="border-r border-slate-200 pr-6">
                <p class="text-sm text-slate-600 font-medium">Budget</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">$<?= number_format($selectedProcurement['budget'], 2) ?></p>
            </div>
            <div class="border-r border-slate-200 pr-6">
                <p class="text-sm text-slate-600 font-medium">Status</p>
                <p class="mt-1 text-lg font-semibold text-slate-900 capitalize"><?= htmlspecialchars($selectedProcurement['status']) ?></p>
            </div>
            <div>
                <p class="text-sm text-slate-600 font-medium">Total Bids</p>
                <p class="mt-1 text-lg font-semibold text-slate-900"><?= count($rankedBids) ?></p>
            </div>
        </div>
    </div>

    <?php if (empty($rankedBids)): ?>
        <div class="bg-slate-100 border border-slate-200 rounded-3xl p-8 text-center">
            <p class="text-slate-600">No bids submitted for this procurement yet.</p>
        </div>
    <?php else: ?>
        <!-- AI Recommendations -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <?php
            $recommendation_types = [
                ['key' => 'best_overall', 'title' => 'Best Overall', 'color' => 'emerald', 'icon' => '★'],
                ['key' => 'lowest_cost', 'title' => 'Lowest Cost', 'color' => 'blue', 'icon' => '💰'],
                ['key' => 'fastest_delivery', 'title' => 'Fastest Delivery', 'color' => 'amber', 'icon' => '⚡'],
                ['key' => 'most_reliable', 'title' => 'Most Reliable', 'color' => 'slate', 'icon' => '✓'],
            ];
            foreach ($recommendation_types as $rec):
                if ($recommendations[$rec['key']]):
                    $bid = $recommendations[$rec['key']];
                    ?>
            <div class="bg-gradient-to-br from-<?= $rec['color'] ?>-50 to-<?= $rec['color'] ?>-100 border border-<?= $rec['color'] ?>-200 rounded-3xl p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <p class="text-sm font-medium text-<?= $rec['color'] ?>-900 uppercase tracking-widest"><?= $rec['icon'] ?> <?= $rec['title'] ?></p>
                        <p class="mt-2 text-lg font-semibold text-slate-900"><?= htmlspecialchars($bid['supplier_name']) ?></p>
                    </div>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Price:</span>
                        <span class="font-semibold text-slate-900">$<?= number_format($bid['price'], 2) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Delivery:</span>
                        <span class="font-semibold text-slate-900"><?= $bid['delivery_days'] ?> days</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Score:</span>
                        <span class="font-semibold text-slate-900"><?= $bid['final_score'] ?>/100</span>
                    </div>
                </div>
            </div>
            <?php
                endif;
            endforeach;
            ?>
        </div>

        <!-- Ranked Bids Table -->
        <div class="bg-white border border-slate-200 rounded-3xl p-8 overflow-x-auto">
            <h3 class="text-lg font-semibold text-slate-900 mb-6">All Bids (AI Ranked)</h3>
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Rank</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Supplier</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Price</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Delivery</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Price Score</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Reliability</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Final Score</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php foreach ($rankedBids as $index => $bid): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <?php if ($index === 0): ?>
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 font-semibold text-xs">1</span>
                            <?php else: ?>
                                <span class="text-slate-600"><?= $index + 1 ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 font-medium text-slate-900"><?= htmlspecialchars($bid['supplier_name']) ?></td>
                        <td class="px-4 py-3 text-slate-600">$<?= number_format($bid['price'], 2) ?></td>
                        <td class="px-4 py-3 text-slate-600"><?= $bid['delivery_days'] ?> days</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-20 h-2 bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500" style="width: <?= $bid['price_score'] ?>%"></div>
                                </div>
                                <span class="text-xs font-semibold text-slate-900"><?= $bid['price_score'] ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-20 h-2 bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-amber-500" style="width: <?= $bid['reliability_score'] ?>%"></div>
                                </div>
                                <span class="text-xs font-semibold text-slate-900"><?= $bid['reliability_score'] ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-semibold text-slate-900"><?= $bid['final_score'] ?></td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-900 capitalize">
                                <?= htmlspecialchars($bid['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Price Analysis -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
            <div class="bg-white border border-slate-200 rounded-3xl p-6">
                <h4 class="font-semibold text-slate-900 mb-4">Price Range Analysis</h4>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Budget:</span>
                        <span class="font-semibold text-slate-900">$<?= number_format($selectedProcurement['budget'], 2) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Lowest Bid:</span>
                        <span class="font-semibold text-emerald-600">$<?= number_format(min(array_column($rankedBids, 'price')), 2) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Average Price:</span>
                        <span class="font-semibold text-slate-900">$<?= number_format(array_sum(array_column($rankedBids, 'price')) / count($rankedBids), 2) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Highest Bid:</span>
                        <span class="font-semibold text-rose-600">$<?= number_format(max(array_column($rankedBids, 'price')), 2) ?></span>
                    </div>
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-3xl p-6">
                <h4 class="font-semibold text-slate-900 mb-4">Delivery Time Analysis</h4>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Target Days:</span>
                        <span class="font-semibold text-slate-900"><?= $selectedProcurement['delivery_days'] ?> days</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Fastest:</span>
                        <span class="font-semibold text-emerald-600"><?= min(array_column($rankedBids, 'delivery_days')) ?> days</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Average:</span>
                        <span class="font-semibold text-slate-900"><?= round(array_sum(array_column($rankedBids, 'delivery_days')) / count($rankedBids), 1) ?> days</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Slowest:</span>
                        <span class="font-semibold text-rose-600"><?= max(array_column($rankedBids, 'delivery_days')) ?> days</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php renderAuthPageEnd(); ?>
