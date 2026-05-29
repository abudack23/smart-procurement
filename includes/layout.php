<?php
function renderHead(string $title = 'Smart Procurement') {
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($title) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x/dist/cdn.min.js"></script>
  <style>
    body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    .form-control input, .form-control textarea, .form-control select { transition: all .2s ease; }
    .table-fixed th, .table-fixed td { white-space: nowrap; }
  </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <?php
}

function renderFlash(): void {
    $flash = getFlash();
    if (!$flash) {
        return;
    }
    $color = $flash['type'] === 'error' ? 'bg-rose-500 text-white' : 'bg-emerald-500 text-white';
    echo '<div class="rounded-3xl p-4 mb-6 shadow-sm ' . $color . '">';
    echo '<p class="text-sm font-medium">' . htmlspecialchars($flash['message']) . '</p>';
    echo '</div>';
}

function renderGuestPageStart(string $title): void {
    renderHead($title);
    ?>
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
      <div class="w-full max-w-md bg-white shadow-2xl rounded-3xl border border-slate-200 p-8">
        <div class="mb-8 text-center">
          <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Smart Procurement</p>
          <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-900"><?= htmlspecialchars($title) ?></h1>
          <p class="mt-2 text-sm text-slate-500">Modern procurement, bidding, and supplier management powered by PHP and MySQL.</p>
        </div>
        <?php renderFlash(); ?>
    <?php
}

function renderGuestPageEnd(): void {
    ?>
      </div>
    </div>
</body>
</html>
    <?php
}

function renderAuthPageStart(string $title, string $active = 'dashboard'): void {
    renderHead($title);
    $user = currentUser();
    ?>
    <div x-data="{ sidebarOpen: false }" class="min-h-screen flex bg-slate-50">
      <aside class="hidden lg:flex lg:w-72 lg:flex-col lg:border-r lg:border-slate-200 lg:bg-white">
        <div class="flex h-20 items-center px-6 border-b border-slate-200">
          <div>
            <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Smart Procurement</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">Enterprise Portal</p>
          </div>
        </div>
        <nav class="flex-1 px-6 py-8 space-y-1 overflow-y-auto">
          <?= renderSidebarNavigation($active) ?>
        </nav>
      </aside>

      <div class="flex-1 lg:overflow-hidden">
        <div class="bg-white border-b border-slate-200 lg:hidden">
          <div class="flex items-center justify-between px-4 py-4">
            <div>
              <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Smart Procurement</p>
              <p class="text-lg font-semibold text-slate-900">Portal</p>
            </div>
            <button type="button" @click="sidebarOpen = !sidebarOpen" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 text-slate-700 hover:bg-slate-100">
              <span x-text="sidebarOpen ? '✕' : '☰'"></span>
            </button>
          </div>
          <div x-show="sidebarOpen" class="border-t border-slate-200 bg-slate-50 px-4 py-5">
            <?= renderSidebarNavigation($active, true) ?>
          </div>
        </div>

        <header class="flex items-center justify-between px-6 py-5 bg-slate-50 border-b border-slate-200">
          <div>
            <h1 class="text-2xl font-semibold text-slate-900"><?= htmlspecialchars($title) ?></h1>
            <?php if ($user): ?>
              <p class="text-sm text-slate-500">Welcome back, <?= htmlspecialchars($user['name']) ?>.</p>
            <?php endif; ?>
          </div>
          <div class="flex items-center gap-3">
            <?php if ($user): ?>
              <div class="hidden sm:flex flex-col text-right">
                <span class="text-sm font-medium text-slate-900"><?= htmlspecialchars($user['name']) ?></span>
                <span class="text-xs uppercase tracking-[0.3em] text-slate-500"><?= htmlspecialchars($user['role']) ?></span>
              </div>
            <?php endif; ?>
          </div>
        </header>

        <main class="px-6 py-8 lg:px-10 lg:py-10 overflow-y-auto">
          <div class="max-w-7xl mx-auto">
            <?php renderFlash(); ?>
    <?php
}

function renderAuthPageEnd(): void {
    ?>
          </div>
        </main>
      </div>
    </div>
</body>
</html>
    <?php
}

function renderSidebarNavigation(string $active, bool $mobile = false): string {
    $user = currentUser();
    $role = $user['role'] ?? 'guest';
    $items = [
        ['label' => 'Dashboard', 'href' => 'dashboard.php', 'key' => 'dashboard'],
    ];
    if (in_array($role, ['admin', 'superadmin'], true)) {
        $items = array_merge($items, [
        ['label' => 'Procurements', 'href' => 'procurements.php', 'key' => 'procurements'],
        ['label' => 'AI Evaluation', 'href' => 'ai_evaluation.php', 'key' => 'ai_evaluation'],
        ['label' => 'Users', 'href' => 'users.php', 'key' => 'users'],
        ['label' => 'Reports', 'href' => 'reports.php', 'key' => 'reports'],
        ]);
    }
    if ($role === 'supplier') {
        $items = array_merge($items, [
            ['label' => 'Opportunities', 'href' => 'opportunities.php', 'key' => 'opportunities'],
            ['label' => 'My Bids', 'href' => 'my_bids.php', 'key' => 'my_bids'],
            ['label' => 'Submit Bid', 'href' => 'submit_bid.php', 'key' => 'submit_bid'],
            ['label' => 'Performance', 'href' => 'performance.php', 'key' => 'performance'],
        ]);
    }
    $items = array_merge($items, [
        ['label' => 'Profile', 'href' => 'profile.php', 'key' => 'profile'],
        ['label' => 'Notifications', 'href' => 'notifications.php', 'key' => 'notifications'],
        ['label' => 'Logout', 'href' => 'logout.php', 'key' => 'logout', 'danger' => true],
    ]);

    $html = '<div class="space-y-1">';
    foreach ($items as $item) {
        $classes = 'block rounded-3xl px-4 py-3 text-sm font-medium transition-colors';
        if ($item['key'] === $active) {
            $classes .= ' bg-slate-900 text-white shadow-sm';
        } else {
            $classes .= ' text-slate-700 hover:bg-slate-100';
        }
        if (!empty($item['danger'])) {
            $classes .= ' text-rose-600 hover:bg-rose-50';
        }
        $html .= sprintf('<a href="%s" class="%s">%s</a>', htmlspecialchars($item['href']), $classes, htmlspecialchars($item['label']));
    }
    $html .= '</div>';
    return $html;
}
