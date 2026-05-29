<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';
requireLogin();
$user = currentUser();

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_single'])) {
    markNotificationRead((int)$_POST['mark_single'], (int)$user['id']);
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all'])) {
    markNotificationsRead($user['id']);
    http_response_code(204);
    exit;
}

$pg = getPaginationParams(10);
$page = $pg['page'];
$perPage = $pg['per_page'];
$offset = $pg['offset'];

$where = 'WHERE user_id = ?';
$params = [$user['id']];
if ($filter === 'unread') {
    $where .= ' AND is_read = 0';
}
if ($search !== '') {
    $where .= ' AND (title LIKE ? OR message LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications ' . $where);
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$stmt = $pdo->prepare('SELECT * FROM notifications ' . $where . ' ORDER BY created_at DESC LIMIT ? OFFSET ?');
$bindIndex = 1;
foreach ($params as $param) {
    $stmt->bindValue($bindIndex++, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue($bindIndex++, (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue($bindIndex++, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll();

renderAuthPageStart('Notifications', 'notifications');
?>
<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
  <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between mb-6">
    <div>
      <h2 class="text-xl font-semibold text-slate-900">Notifications</h2>
      <p class="text-sm text-slate-500">Recent system alerts and activity updates.</p>
    </div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
      <form method="get" class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <label class="sr-only" for="search">Search notifications</label>
        <input id="search" name="search" type="text" value="<?= htmlspecialchars($search) ?>" placeholder="Search notifications" class="min-w-[220px] rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
        <label class="sr-only" for="filter">Filter</label>
        <select id="filter" name="filter" class="rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-slate-900 focus:ring-2 focus:ring-slate-200">
          <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
          <option value="unread" <?= $filter === 'unread' ? 'selected' : '' ?>>Unread</option>
        </select>
        <button type="submit" class="rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
      </form>
      <span class="rounded-full bg-emerald-50 px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Unread alerts are cleared as you view this page</span>
    </div>
  </div>
  <?php if (empty($notifications)): ?>
    <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">No notifications yet.</div>
  <?php else: ?>
    <div class="space-y-4">
      <?php foreach ($notifications as $note): $isUnread = (int)$note['is_read'] === 0; ?>
        <article data-notification-id="<?= (int)$note['id'] ?>" data-read="<?= (int)$note['is_read'] ?>" class="cursor-pointer rounded-3xl border p-5 shadow-sm transition duration-150 hover:-translate-y-0.5 hover:shadow-md <?= $isUnread ? 'border-indigo-200 bg-indigo-50/95' : 'border-slate-200 bg-slate-50/90 opacity-90' ?>">
          <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3">
              <span class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-base shadow-sm"><?= getNotificationIcon($note['type']) ?></span>
              <div>
                <p class="text-sm font-semibold text-slate-900 <?= $isUnread ? 'text-slate-950' : 'text-slate-700' ?>"><?= htmlspecialchars($note['title']) ?></p>
                <p class="mt-1 text-sm text-slate-500"><?= date('M j, Y g:ia', strtotime($note['created_at'])) ?></p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <?php if ($isUnread): ?>
                <span data-read-badge="true" class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700">Unread</span>
              <?php else: ?>
                <span data-read-badge="true" class="rounded-full bg-white/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 shadow-sm">Read</span>
              <?php endif; ?>
              <span class="rounded-full bg-white/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-600 shadow-sm"><?= htmlspecialchars($note['type']) ?></span>
            </div>
          </div>
          <p class="mt-4 text-sm leading-6 text-slate-700"><?= nl2br(htmlspecialchars($note['message'])) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
    <?php renderPaginationControls('notifications.php', $page, $perPage, $total); ?>
  <?php endif; ?>
</div>
<script>
  window.addEventListener('load', () => {
    fetch('notifications.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: 'mark_all=1'
    }).then(() => {
      document.querySelectorAll('[data-notification-id]').forEach((card) => {
        card.dataset.read = '1';
        card.classList.remove('border-indigo-200', 'bg-indigo-50/95');
        card.classList.add('border-slate-200', 'bg-slate-50/90', 'opacity-90');
        const badge = card.querySelector('[data-read-badge="true"]');
        if (badge) {
          badge.className = 'rounded-full bg-white/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 shadow-sm';
          badge.textContent = 'Read';
        }
      });
    }).catch(() => {});
  });

  document.querySelectorAll('[data-notification-id]').forEach((card) => {
    card.addEventListener('click', () => {
      if (card.dataset.read === '1') return;
      fetch('notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: 'mark_single=' + encodeURIComponent(card.dataset.notificationId)
      }).then(() => {
        card.dataset.read = '1';
        card.classList.remove('border-indigo-200', 'bg-indigo-50/95');
        card.classList.add('border-slate-200', 'bg-slate-50/90', 'opacity-90');
        const badge = card.querySelector('[data-read-badge="true"]');
        if (badge) {
          badge.className = 'rounded-full bg-white/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 shadow-sm';
          badge.textContent = 'Read';
        }
      }).catch(() => {});
    });
  });
</script>
<?php
renderAuthPageEnd();
