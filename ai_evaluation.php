<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/ai.php';
requireRole('admin');

$selectedProcurementId = (int)($_GET['procurement_id'] ?? 0);
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'score';
$pg = getPaginationParams(10);
$page = $pg['page'];
$perPage = $pg['per_page'];
$offset = $pg['offset'];
$selectedProcurement = null;
$allRankedBids = [];
$rankedBids = [];
$recommendations = [];

if ($selectedProcurementId) {
    $selectedProcurement = getProcurementById($selectedProcurementId);
    if ($selectedProcurement) {
        $allRankedBids = getRankedBidsForProcurement($selectedProcurementId);
        $filtered = $allRankedBids;

        if ($search !== '') {
            $needle = strtolower($search);
            $filtered = array_values(array_filter($filtered, function($bid) use ($needle) {
                return stripos((string)$bid['supplier_name'], $needle) !== false
                    || stripos((string)$bid['status'], $needle) !== false
                    || stripos((string)$bid['price'], $needle) !== false
                    || stripos((string)$bid['delivery_days'], $needle) !== false
                    || stripos((string)$bid['reliability_score'], $needle) !== false
                    || stripos((string)$bid['final_score'], $needle) !== false;
            }));
        }

        if ($filter !== 'all') {
            $filtered = array_values(array_filter($filtered, function($bid) use ($filter) {
                if ($filter === 'recommended') {
                    return stripos((string)($bid['rank_badge'] ?? ''), 'best') !== false || stripos((string)($bid['rank_badge'] ?? ''), 'lowest') !== false || stripos((string)($bid['rank_badge'] ?? ''), 'fastest') !== false || stripos((string)($bid['rank_badge'] ?? ''), 'reliable') !== false;
                }
                return strtolower((string)$bid['status']) === $filter || strtolower((string)($bid['rank_badge'] ?? '')) === $filter;
            }));
        }

        usort($filtered, function($a, $b) use ($sort) {
            if ($sort === 'price') return (float)$a['price'] <=> (float)$b['price'];
            if ($sort === 'reliability') return (int)$b['reliability_score'] <=> (int)$a['reliability_score'];
            if ($sort === 'delivery') return (int)$a['delivery_days'] <=> (int)$b['delivery_days'];
            if ($sort === 'newest') return strtotime((string)$b['created_at']) <=> strtotime((string)$a['created_at']);
            return (int)$b['final_score'] <=> (int)$a['final_score'];
        });

        $total = count($filtered);
        $rankedBids = array_slice($filtered, $offset, $perPage);
        if (!empty($filtered)) {
            $recommendations = [
                'best_overall' => $filtered[0],
                'lowest_cost' => array_reduce($filtered, function($carry, $bid) {
                    return $carry === null || $bid['price'] < $carry['price'] ? $bid : $carry;
                }, null),
                'fastest_delivery' => array_reduce($filtered, function($carry, $bid) {
                    return $carry === null || $bid['delivery_days'] < $carry['delivery_days'] ? $bid : $carry;
                }, null),
                'most_reliable' => array_reduce($filtered, function($carry, $bid) {
                    return $carry === null || $bid['reliability_score'] > $carry['reliability_score'] ? $bid : $carry;
                }, null),
            ];
        }
    }
}

$procurements = getRecentProcurements(200);

renderAuthPageStart('AI Evaluation', 'ai_evaluation');
?>
<div class="mb-8">
    <p class="text-slate-600 mb-6">Review procurement bids using AI-powered scoring to rank suppliers, highlight risks, and make faster award decisions.</p>
</div>

<div class="bg-white border border-slate-200 rounded-3xl p-8 mb-8">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Select procurement</h2>
            <p class="mt-1 text-sm text-slate-500">Search by title, ID, status, or budget to jump directly to the right opportunity.</p>
        </div>
        <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-indigo-700">Keyboard friendly</span>
    </div>
    <form method="get" id="procurementForm" class="mt-6 space-y-4">
        <div class="relative" id="procurementPicker">
            <label for="procurementSearch" class="block text-sm font-medium text-slate-700 mb-2">Search procurement</label>
            <div class="relative">
                <input id="procurementSearch" type="search" autocomplete="off" spellcheck="false" value="<?= $selectedProcurement ? htmlspecialchars($selectedProcurement['title']) : '' ?>" placeholder="Type procurement title, ID, status, or budget" class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm text-slate-900 shadow-sm transition focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                <button type="button" id="procurementToggle" aria-label="Toggle procurement list" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-white p-2 text-slate-500 shadow-sm hover:bg-slate-100">▾</button>
            </div>
            <input type="hidden" name="procurement_id" id="procurement_id" value="<?= (int)$selectedProcurementId ?>" />
            <div id="procurementMenu" class="absolute z-20 mt-2 hidden w-full rounded-3xl border border-slate-200 bg-white p-2 shadow-xl ring-1 ring-slate-200">
                <div class="mb-2 flex items-center justify-between px-3 text-xs uppercase tracking-[0.18em] text-slate-500">
                    <span>Procurements</span>
                    <span>↑↓ to navigate</span>
                </div>
                <div class="max-h-72 overflow-y-auto pr-1">
                    <?php foreach ($procurements as $p): ?>
                        <button type="button" class="procurement-option flex w-full flex-col rounded-2xl border border-transparent px-3 py-3 text-left transition hover:border-slate-200 hover:bg-slate-50 focus:border-slate-300 focus:bg-slate-50 focus:outline-none" data-id="<?= (int)$p['id'] ?>" data-title="<?= htmlspecialchars($p['title']) ?>" data-status="<?= htmlspecialchars($p['status']) ?>" data-budget="<?= htmlspecialchars($p['budget'] ?: 'TBD') ?>" data-deadline="<?= date('M j, Y', strtotime($p['submission_deadline'])) ?>">
                            <span class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($p['title']) ?></span>
                            <span class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                <span class="rounded-full bg-slate-100 px-2 py-1">ID #<?= (int)$p['id'] ?></span>
                                <span class="rounded-full bg-emerald-50 px-2 py-1 text-emerald-700"><?= htmlspecialchars($p['status']) ?></span>
                                <span class="rounded-full bg-slate-100 px-2 py-1">Budget: <?= htmlspecialchars($p['budget'] ?: 'TBD') ?></span>
                                <span class="rounded-full bg-slate-100 px-2 py-1">Due <?= date('M j, Y', strtotime($p['submission_deadline'])) ?></span>
                            </span>
                        </button>
                    <?php endforeach; ?>
                    <div class="empty-state hidden px-3 py-4 text-sm text-slate-500">No procurement matches your search.</div>
                </div>
            </div>
        </div>
        <p class="text-xs text-slate-500">Tip: click an option or use the arrow keys and Enter to select a procurement instantly.</p>
    </form>
</div>
<script>
  (function () {
    const picker = document.getElementById('procurementPicker');
    const form = document.getElementById('procurementForm');
    const searchInput = document.getElementById('procurementSearch');
    const menu = document.getElementById('procurementMenu');
    const hiddenInput = document.getElementById('procurement_id');
    const toggleButton = document.getElementById('procurementToggle');
    const options = Array.from(document.querySelectorAll('.procurement-option'));
    const emptyState = menu.querySelector('.empty-state');
    let activeIndex = -1;

    function debounce(fn, wait) {
      let timer;
      return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), wait);
      };
    }

    function openMenu() {
      menu.classList.remove('hidden');
      filterOptions(searchInput.value);
    }

    function closeMenu() {
      menu.classList.add('hidden');
      activeIndex = -1;
    }

    function filterOptions(value) {
      const query = value.toLowerCase();
      let visibleCount = 0;
      options.forEach((option, index) => {
        const text = [option.dataset.title, option.dataset.id, option.dataset.status, option.dataset.budget, option.dataset.deadline].join(' ').toLowerCase();
        const show = text.includes(query);
        option.style.display = show ? '' : 'none';
        if (show) visibleCount += 1;
        if (show && index === activeIndex) {
          option.classList.add('bg-slate-100', 'border-slate-200');
        }
      });
      emptyState.classList.toggle('hidden', visibleCount !== 0);
      if (visibleCount === 0) {
        activeIndex = -1;
      }
    }

    function selectOption(option) {
      hiddenInput.value = option.dataset.id;
      searchInput.value = option.dataset.title;
      closeMenu();
      form.submit();
    }

    function moveSelection(step) {
      const visible = options.filter((option) => option.style.display !== 'none');
      if (!visible.length) return;
      activeIndex = activeIndex < 0 ? (step > 0 ? 0 : visible.length - 1) : Math.max(0, Math.min(visible.length - 1, activeIndex + step));
      options.forEach((option) => option.classList.remove('bg-slate-100', 'border-slate-200'));
      visible[activeIndex].classList.add('bg-slate-100', 'border-slate-200');
      visible[activeIndex].scrollIntoView({ block: 'nearest' });
    }

    searchInput.addEventListener('focus', openMenu);
    searchInput.addEventListener('input', debounce(function () {
      openMenu();
      filterOptions(searchInput.value);
    }, 120));
    toggleButton.addEventListener('click', function () {
      if (menu.classList.contains('hidden')) openMenu(); else closeMenu();
    });
    searchInput.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        openMenu();
        moveSelection(1);
      }
      if (event.key === 'ArrowUp') {
        event.preventDefault();
        openMenu();
        moveSelection(-1);
      }
      if (event.key === 'Enter' && activeIndex >= 0) {
        event.preventDefault();
        const visible = options.filter((option) => option.style.display !== 'none');
        if (visible[activeIndex]) selectOption(visible[activeIndex]);
      }
      if (event.key === 'Escape') {
        closeMenu();
      }
    });
    options.forEach((option) => {
      option.addEventListener('click', function () {
        selectOption(option);
      });
    });
    document.addEventListener('click', function (event) {
      if (!picker.contains(event.target)) closeMenu();
    });
    filterOptions(searchInput.value);
  })();
</script>

<?php if ($selectedProcurement): ?>
    <div class="mb-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sticky top-4 z-10">
        <form method="get" class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr_0.8fr_0.6fr]">
            <input type="hidden" name="procurement_id" value="<?= $selectedProcurementId ?>" />
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Search</span>
                <input type="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Supplier, status, score..." class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" />
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Filter</span>
                <select name="filter" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All bids</option>
                    <option value="recommended" <?= $filter === 'recommended' ? 'selected' : '' ?>>Recommended</option>
                    <option value="awarded" <?= $filter === 'awarded' ? 'selected' : '' ?>>Awarded</option>
                    <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="rejected" <?= $filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Sort</span>
                <select name="sort" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <option value="score" <?= $sort === 'score' ? 'selected' : '' ?>>Highest AI score</option>
                    <option value="price" <?= $sort === 'price' ? 'selected' : '' ?>>Lowest bid price</option>
                    <option value="reliability" <?= $sort === 'reliability' ? 'selected' : '' ?>>Highest reliability</option>
                    <option value="delivery" <?= $sort === 'delivery' ? 'selected' : '' ?>>Fastest delivery</option>
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest evaluations</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Rows</span>
                <select name="per_page" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50</option>
                </select>
            </label>
            <div class="xl:col-span-4 flex flex-wrap items-center gap-3">
                <button type="submit" class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Apply filters</button>
                <a href="ai_evaluation.php?procurement_id=<?= $selectedProcurementId ?>" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
            </div>
        </form>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.4fr_0.6fr] mb-8">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Procurement overview</h3>
            <div class="space-y-3 text-sm text-slate-600">
                <div class="flex justify-between">
                    <span>Title</span>
                    <span class="font-semibold text-slate-900"><?= htmlspecialchars($selectedProcurement['title']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Budget</span>
                    <span class="font-semibold text-slate-900"><?= htmlspecialchars($selectedProcurement['budget'] ?: 'TBD') ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Deadline</span>
                    <span class="font-semibold text-slate-900"><?= date('M j, Y', strtotime($selectedProcurement['submission_deadline'])) ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Delivery target</span>
                    <span class="font-semibold text-slate-900"><?= htmlspecialchars($selectedProcurement['delivery_days']) ?> days</span>
                </div>
                <div>
                    <span class="text-slate-500">Evaluation criteria</span>
                    <p class="mt-2 text-slate-900"><?= htmlspecialchars($selectedProcurement['evaluation_criteria'] ?: 'Price, delivery, reliability and compliance.') ?></p>
                </div>
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">AI recommendations</h3>
            <?php if (empty($rankedBids)): ?>
                <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">No bids have been submitted yet for this procurement.</div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($recommendations as $label => $bid): ?>
                        <?php if ($bid): ?>
                            <div class="rounded-3xl border border-slate-200 bg-gradient-to-r from-slate-50 to-indigo-50 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                <p class="text-sm font-semibold text-slate-900"><?= ucwords(str_replace('_', ' ', $label)) ?></p>
                                <span class="mt-2 inline-flex rounded-full bg-gradient-to-r from-indigo-600 to-violet-500 px-3 py-1 text-xs font-semibold text-white">AI Recommended</span>
                                <p class="mt-2 text-sm text-slate-600">Supplier: <?= htmlspecialchars($bid['supplier_name']) ?></p>
                                <p class="text-sm text-slate-600">Price: ₱<?= number_format($bid['price'], 2) ?> • Delivery: <?= htmlspecialchars($bid['delivery_days']) ?> days</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">Score: <?= $bid['final_score'] ?>/100</p>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($rankedBids)): ?>
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm mb-8">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Ranked supplier bids</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Rank</th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3">Bid price</th>
                            <th class="px-4 py-3">Delivery</th>
                            <th class="px-4 py-3">Price score</th>
                            <th class="px-4 py-3">Reliability</th>
                            <th class="px-4 py-3">Final score</th>
                            <th class="px-4 py-3">Recommendation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($rankedBids as $index => $bid): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3"><?= $offset + $index + 1 ?></td>
                                <td class="px-4 py-3 font-medium text-slate-900"><?= htmlspecialchars($bid['supplier_name']) ?></td>
                                <td class="px-4 py-3">₱<?= number_format($bid['price'], 2) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($bid['delivery_days']) ?> days</td>
                                <td class="px-4 py-3"><?= $bid['price_score'] ?></td>
                                <td class="px-4 py-3"><?= ($bid['reliability_score'] > 0 ? $bid['reliability_score'] . '/100' : 'Not Yet Rated') ?></td>
                                <td class="px-4 py-3 font-semibold text-slate-900"><?= $bid['final_score'] ?></td>
                                <td class="px-4 py-3"><span class="<?= getStatusBadgeClass($bid['rank_badge'] ?? 'recommended') ?>"><?= htmlspecialchars($bid['rank_badge'] ?? 'AI Recommended') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-6 flex items-center justify-between text-sm text-slate-500">
                <span>Showing <?= $total === 0 ? 0 : $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> of <?= $total ?> evaluations</span>
                <?php renderPaginationControls('ai_evaluation.php?procurement_id=' . $selectedProcurementId . '&search=' . urlencode($search) . '&filter=' . urlencode($filter) . '&sort=' . urlencode($sort), $page, $perPage, $total); ?>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-3xl border border-slate-200 bg-white p-6">
                <h4 class="font-semibold text-slate-900 mb-4">Cost comparison</h4>
                <div class="space-y-3 text-sm text-slate-600">
                    <div class="flex justify-between">
                        <span>Lowest bid</span>
                        <span>₱<?= number_format(min(array_column($rankedBids, 'price')), 2) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Average bid</span>
                        <span>₱<?= number_format(array_sum(array_column($rankedBids, 'price')) / count($rankedBids), 2) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Highest bid</span>
                        <span>₱<?= number_format(max(array_column($rankedBids, 'price')), 2) ?></span>
                    </div>
                </div>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-6">
                <h4 class="font-semibold text-slate-900 mb-4">Delivery comparison</h4>
                <div class="space-y-3 text-sm text-slate-600">
                    <div class="flex justify-between">
                        <span>Fastest</span>
                        <span><?= min(array_column($rankedBids, 'delivery_days')) ?> days</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Average</span>
                        <span><?= round(array_sum(array_column($rankedBids, 'delivery_days')) / count($rankedBids), 1) ?> days</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Slowest</span>
                        <span><?= max(array_column($rankedBids, 'delivery_days')) ?> days</span>
                    </div>
                </div>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-6">
                <h4 class="font-semibold text-slate-900 mb-4">Reliability comparison</h4>
                <div class="space-y-3 text-sm text-slate-600">
                    <div class="flex justify-between">
                        <span>Highest reliability</span>
                        <span><?= max(array_column($rankedBids, 'reliability_score')) ?>/100</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Average reliability</span>
                        <span><?= round(array_sum(array_column($rankedBids, 'reliability_score')) / count($rankedBids), 1) ?>/100</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php renderAuthPageEnd();
