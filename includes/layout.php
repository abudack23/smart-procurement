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
    :root {
      --aclc-blue: #1e0178;
      --aclc-red: #db261f;
      --aclc-white: #ffffff;
      --aclc-surface: #f8fafc;
      --aclc-surface-strong: #ffffff;
      --aclc-text: #0f172a;
      --aclc-muted: #cbd5e1;
    }

    body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: var(--aclc-surface); color: var(--aclc-text); }
    .form-control input,
    .form-control textarea,
    .form-control select { transition: all .2s ease; }
    .table-fixed th,
    .table-fixed td { white-space: nowrap; }
    .shadow-soft { box-shadow: 0 30px 85px -55px rgba(15, 23, 42, 0.1), 0 1px 2px rgba(15, 23, 42, 0.05); }
    .shadow-brand { box-shadow: 0 24px 48px -24px rgba(30, 1, 120, 0.16); }
    .bg-glass { background: rgba(255,255,255,0.76); backdrop-filter: blur(16px); }

    .aclc-sidebar { background: var(--aclc-blue); color: var(--aclc-white); }
    .aclc-sidebar .sidebar-brand { color: #f8fafc; }
    .aclc-sidebar .sidebar-brand span { color: rgba(255,255,255,0.7); }
    .aclc-sidebar .sidebar-nav { min-height: 1px; }
    .aclc-sidebar .sidebar-link { display: block; border-radius: 1rem; padding: 0.75rem 1rem; font-weight: 600; color: rgba(255,255,255,0.9); transition: background-color .2s ease, color .2s ease; }
    .aclc-sidebar .sidebar-link:hover { background: rgba(255,255,255,0.12); color: #ffffff; }
    .aclc-sidebar .sidebar-link.active { background: rgba(255,255,255,0.16); color: #ffffff; }
    .aclc-sidebar .sidebar-footer { border-top: 1px solid rgba(255,255,255,0.12); }
    .aclc-sidebar .sidebar-footer p { color: rgba(255,255,255,0.82); }
    .aclc-sidebar .sidebar-footer small { color: rgba(255,255,255,0.66); }

    .btn-primary { background: var(--aclc-blue); color: var(--aclc-white); }
    .btn-primary:hover { background: #13034f; }
    .btn-secondary { border-color: var(--aclc-blue); color: var(--aclc-blue); background: var(--aclc-white); }
    .btn-secondary:hover { background: rgba(30,1,120,0.08); }
    .btn-danger { background: var(--aclc-red); color: var(--aclc-white); }
    .btn-danger:hover { background: #a11a18; }
    .btn-success { background: #16a34a; color: var(--aclc-white); }
    .card-surface { background: var(--aclc-white); border-color: rgba(30,1,120,0.08); }
    .page-panel { background: var(--aclc-white); }
    .text-aclc-blue { color: var(--aclc-blue); }
    .border-aclc-blue { border-color: var(--aclc-blue); }
    .bg-aclc-blue { background: var(--aclc-blue); }
    .bg-aclc-surface { background: var(--aclc-surface); }
    .bg-aclc-white { background: var(--aclc-white); }

    .bg-slate-900 { background-color: var(--aclc-blue) !important; }
    .hover\:bg-slate-800:hover { background-color: #13034f !important; }
    .border-slate-900 { border-color: var(--aclc-blue) !important; }
  </style>
</head>
<body class="min-h-screen bg-aclc-surface text-slate-900">
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
    <div class="min-h-screen bg-slate-100">
      <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-[2rem] bg-white p-8 shadow-soft ring-1 ring-slate-200">
          <div class="mb-8 text-center">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Smart Procurement</p>
            <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-900"><?= htmlspecialchars($title) ?></h1>
            <p class="mt-3 mx-auto max-w-2xl text-sm leading-6 text-slate-600">Modern procurement and bidding for suppliers and administrators, built with PHP, MySQL, and Tailwind UI.</p>
          </div>
          <?php renderFlash(); ?>
    <?php
}

function renderGuestPageEnd(): void {
    ?>
        </div>
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
    <div x-data="{ sidebarOpen: false, userMenuOpen: false }" class="min-h-screen flex bg-aclc-surface text-slate-900">
      <aside class="hidden lg:flex lg:w-80 lg:flex-col lg:min-h-screen lg:border-r lg:border-slate-200 aclc-sidebar">
        <div class="flex h-24 items-center gap-4 px-6 border-b border-white/10 sidebar-brand">
          <div class="flex h-12 w-12 items-center justify-center rounded-3xl bg-white text-lg font-semibold text-aclc-blue">ACLC</div>
          <div>
            <p class="text-xs uppercase tracking-[0.3em] text-white/70">ACLC Tacloban</p>
            <p class="mt-1 text-lg font-semibold text-white">Smart Procurement</p>
          </div>
        </div>
        <nav class="flex-1 overflow-y-auto px-6 py-6 sidebar-nav">
          <?= renderSidebarNavigation($active) ?>
        </nav>

        <div class="px-6 py-6 sidebar-footer">
          <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-3xl bg-white text-sm font-semibold text-aclc-blue uppercase">U</div>
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-white"><?= htmlspecialchars($user['name'] ?? 'User') ?></p>
              <p class="truncate text-xs uppercase tracking-[0.3em] text-white/80"><?= htmlspecialchars($user['role'] ?? '') ?></p>
            </div>
          </div>
          <div class="mt-4">
            <a href="logout.php" class="block rounded-3xl bg-aclc-red px-4 py-3 text-center text-sm font-semibold text-white hover:bg-[#a11a18]">Sign out</a>
          </div>
        </div>
      </aside>

      <div class="flex-1">
        <div class="lg:hidden bg-aclc-blue text-white border-b border-aclc-blue/80">
          <div class="flex items-center justify-between px-4 py-4">
            <div class="flex items-center gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-base font-semibold text-aclc-blue">A</div>
              <div>
                <p class="text-sm font-semibold">ACLC Procurement</p>
              </div>
            </div>
            <button type="button" @click="sidebarOpen = !sidebarOpen" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/20 bg-white text-aclc-blue">☰</button>
          </div>
          <div x-show="sidebarOpen" x-cloak class="border-t border-white/10 px-4 py-4 bg-white text-aclc-text">
            <div class="space-y-4">
              <?= renderSidebarNavigation($active, true) ?>
            </div>
          </div>
        </div>

        <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl">
          <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <div>
              <h1 class="text-2xl font-semibold text-slate-900"><?= htmlspecialchars($title) ?></h1>
              <?php if ($user): ?>
                <p class="mt-1 text-sm text-slate-500">Welcome back, <?= htmlspecialchars($user['name']) ?>.</p>
              <?php endif; ?>
            </div>
            <div class="flex flex-wrap items-center gap-3">
              <?php if ($user): ?>
                <?php $unreadCount = getUnreadNotificationCount($user['id']); ?>
                <a href="notifications.php" class="relative inline-flex h-11 w-11 items-center justify-center rounded-3xl border border-slate-200 bg-white text-slate-900 hover:bg-slate-50">
                  <span class="text-lg">🔔</span>
                  <?php if ($unreadCount > 0): ?>
                    <span class="absolute -top-1 -right-1 inline-flex min-w-[1.35rem] items-center justify-center rounded-full bg-aclc-red px-1.5 py-0.5 text-[11px] font-semibold text-white"><?= $unreadCount ?></span>
                  <?php endif; ?>
                </a>
                <div class="relative">
                  <button type="button" @click="userMenuOpen = !userMenuOpen" @keydown.escape="userMenuOpen = false" class="inline-flex items-center gap-2 rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                    <span class="hidden sm:inline-flex"><?= htmlspecialchars($user['role'] === 'superadmin' ? 'Super Admin' : ($user['role'] === 'admin' ? 'Admin' : $user['name'])) ?></span>
                    <span class="text-slate-500">▼</span>
                  </button>
                  <div x-show="userMenuOpen" x-cloak @click.outside="userMenuOpen = false" class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-soft">
                    <a href="profile.php" class="block px-4 py-3 text-sm text-slate-900 hover:bg-slate-50">Profile</a>
                    <a href="profile.php#change-password" class="block px-4 py-3 text-sm text-slate-900 hover:bg-slate-50">Change Password</a>
                    <div class="border-t border-slate-200"></div>
                    <a href="logout.php" class="block px-4 py-3 text-sm font-semibold text-aclc-red hover:bg-slate-50">Sign Out</a>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </header>

        <main class="px-4 py-8 sm:px-6 lg:px-10">
          <div class="mx-auto max-w-7xl">
            <?php renderFlash(); ?>
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
      ['label' => 'Bid Management', 'href' => 'view_bids.php', 'key' => 'bid_management'],
      ['label' => 'AI Evaluation', 'href' => 'ai_evaluation.php', 'key' => 'ai_evaluation'],
      ['label' => 'Reports', 'href' => 'reports.php', 'key' => 'reports'],
      ['label' => 'Users', 'href' => 'users.php', 'key' => 'users'],
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
    // Profile and Notifications are available from the header to avoid duplicate access points in the sidebar.

    $html = '<div class="space-y-2">';
    foreach ($items as $item) {
        $classes = 'sidebar-link';
        if ($item['key'] === $active) {
            $classes .= ' active';
        }
        $html .= sprintf('<a href="%s" class="%s">%s</a>', htmlspecialchars($item['href']), $classes, htmlspecialchars($item['label']));
    }
    $html .= '</div>';
    return $html;
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
