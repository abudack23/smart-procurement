<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

renderGuestPageStart('Welcome');
?>
<div class="space-y-6">
  <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6 text-slate-700">
    <h2 class="text-xl font-semibold text-slate-900">Smart Procurement</h2>
    <p class="mt-3 text-sm text-slate-500">A modern procurement and bidding system built with PHP, MySQL, and Tailwind UI. Log in to manage procurement opportunities, submit bids, and access role-based dashboards.</p>
  </div>
  <div class="grid gap-4 sm:grid-cols-2">
    <a href="supplier_login.php" class="inline-flex items-center justify-center rounded-3xl bg-slate-900 px-4 py-4 text-sm font-semibold text-white hover:bg-slate-800">Supplier sign in</a>
    <a href="register.php" class="inline-flex items-center justify-center rounded-3xl border border-slate-200 bg-white px-4 py-4 text-sm font-semibold text-slate-900 hover:bg-slate-50">Register as supplier</a>
  </div>
</div>
<?php
renderGuestPageEnd();
