<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

renderGuestPageStart('Smart Procurement and Bidding System');
?>
<div class="space-y-12">
  <section class="bg-white shadow-soft ring-1 ring-slate-200 rounded-[2rem] p-8 lg:p-10">
    <div class="grid gap-10 lg:grid-cols-[1.4fr_1fr] items-center">
      <div class="space-y-8">
        <div class="max-w-2xl">
          <p class="text-xs uppercase tracking-[0.3em] text-[#1e0178]">ACLC College of Tacloban</p>
          <h1 class="mt-4 text-5xl font-semibold tracking-tight text-slate-900 sm:text-6xl">Smart Procurement and Bidding System</h1>
          <p class="mt-6 text-lg leading-8 text-slate-600">A centralized platform that streamlines procurement management, supplier bidding, AI-assisted evaluation, predictive analytics, reporting, and transparency.</p>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
          <a href="supplier_login.php" class="inline-flex items-center justify-center rounded-3xl bg-[#1e0178] px-7 py-4 text-sm font-semibold text-white shadow-sm shadow-[#1e0178]/20 transition hover:bg-[#13034f]">Supplier Sign In</a>
          <a href="register.php" class="inline-flex items-center justify-center rounded-3xl border border-[#1e0178] bg-white px-7 py-4 text-sm font-semibold text-[#1e0178] transition hover:bg-[#1e0178]/5">Register as Supplier</a>
          <a href="admin_login.php" class="inline-flex items-center justify-center rounded-3xl border border-slate-300 bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-700 transition hover:border-[#1e0178] hover:text-[#1e0178]">Admin Portal</a>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
          <div class="rounded-3xl bg-[#f8fafc] p-5 text-sm text-slate-700">
            <p class="font-semibold text-[#1e0178]">Procurement Management</p>
            <p class="mt-2">Manage bids, approvals, and awards in one place.</p>
          </div>
          <div class="rounded-3xl bg-[#f8fafc] p-5 text-sm text-slate-700">
            <p class="font-semibold text-[#1e0178]">AI Evaluation</p>
            <p class="mt-2">Use predictive scoring to compare supplier proposals.</p>
          </div>
          <div class="rounded-3xl bg-[#f8fafc] p-5 text-sm text-slate-700">
            <p class="font-semibold text-[#1e0178]">Transparent Reporting</p>
            <p class="mt-2">Track performance and audit activity with confidence.</p>
          </div>
        </div>
      </div>

      <div class="space-y-6 rounded-[2rem] bg-[#1e0178] p-8 text-white shadow-brand sm:p-10">
        <div class="flex items-center justify-between gap-4">
          <div>
            <p class="text-sm uppercase tracking-[0.3em] text-[#c7d2fe]">Live Analytics</p>
            <h2 class="mt-4 text-3xl font-semibold">Procurement Dashboard</h2>
          </div>
          <div class="rounded-3xl border border-white/20 bg-white/10 px-4 py-2 text-sm text-white/90">AI + Predictive</div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div class="rounded-[1.75rem] bg-white/10 p-5">
            <p class="text-3xl font-semibold">82%</p>
            <p class="mt-2 text-sm text-slate-100/80">Bid accuracy</p>
          </div>
          <div class="rounded-[1.75rem] bg-white/10 p-5">
            <p class="text-3xl font-semibold">14</p>
            <p class="mt-2 text-sm text-slate-100/80">Live opportunities</p>
          </div>
        </div>

        <div class="rounded-[1.75rem] bg-white/10 p-5 text-sm text-slate-100/90">
          <div class="flex items-center justify-between">
            <span>Supplier bids</span>
            <span class="font-semibold">148</span>
          </div>
          <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/20">
            <div class="h-full w-8/12 rounded-full bg-[#db261f]"></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="grid gap-6 lg:grid-cols-3">
    <?php $features = [
      ['title' => 'Procurement Management', 'description' => 'Configure RFQs, approvals, and awards in a unified portal.'],
      ['title' => 'Supplier Bidding', 'description' => 'Enable suppliers to submit, revise, and track bids easily.'],
      ['title' => 'AI Evaluation', 'description' => 'Use predictive scoring and recommendation insights.'],
      ['title' => 'Predictive Analytics', 'description' => 'Surface procurement trends, risks, and performance.'],
      ['title' => 'Reports & Analytics', 'description' => 'Generate audit-ready summaries and dashboards.'],
      ['title' => 'Audit Trail', 'description' => 'Maintain transparent logs for every decision and action.'],
    ]; ?>
    <?php foreach ($features as $feature): ?>
      <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
        <div class="flex h-12 w-12 items-center justify-center rounded-3xl bg-[#1e0178]/10 text-[#1e0178]">✓</div>
        <h3 class="mt-5 text-lg font-semibold text-slate-900"><?= htmlspecialchars($feature['title']) ?></h3>
        <p class="mt-3 text-sm leading-6 text-slate-600"><?= htmlspecialchars($feature['description']) ?></p>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="rounded-[2rem] bg-white p-8 shadow-soft ring-1 ring-slate-200">
    <div class="flex flex-col gap-6 lg:gap-8">
      <div class="flex items-center justify-between gap-4">
        <div>
          <p class="text-xs uppercase tracking-[0.3em] text-[#1e0178]">How it works</p>
          <h2 class="mt-2 text-3xl font-semibold text-slate-900">A clear procurement workflow</h2>
        </div>
      </div>
      <div class="overflow-x-auto pb-2">
        <div class="flex min-w-[720px] items-center gap-3 lg:gap-4">
          <?php $steps = ['Create Procurement', 'Supplier Submits Bid', 'AI Evaluation', 'Award Decision', 'Performance Monitoring']; ?>
          <?php foreach ($steps as $index => $step): ?>
            <div class="flex min-w-[220px] flex-col gap-3 rounded-[1.75rem] border border-slate-200 bg-[#f8fafc] p-5 text-sm text-slate-700 shadow-sm">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-3xl bg-[#1e0178] text-sm font-semibold text-white"><?= $index + 1 ?></div>
                <p class="font-semibold text-slate-900"><?= htmlspecialchars($step) ?></p>
              </div>
              <?php if ($index === 2): ?>
                <p class="text-sm text-slate-600">Smart scoring powered by AI.</p>
              <?php endif; ?>
            </div>
            <?php if ($index < count($steps) - 1): ?>
              <div class="hidden h-10 w-12 items-center justify-center rounded-full bg-[#1e0178]/10 text-[#1e0178] text-lg font-semibold lg:flex">→</div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <section class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-[2rem] bg-white p-8 shadow-soft ring-1 ring-slate-200">
      <p class="text-xs uppercase tracking-[0.3em] text-[#1e0178]">Procurement Officers</p>
      <h2 class="mt-4 text-2xl font-semibold text-slate-900">Accelerate decision-making</h2>
      <ul class="mt-6 space-y-4 text-sm text-slate-600">
        <li class="flex gap-3"><span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#1e0178] text-xs font-semibold text-white">✓</span>Faster evaluation cycles</li>
        <li class="flex gap-3"><span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#1e0178] text-xs font-semibold text-white">✓</span>Better transparency</li>
        <li class="flex gap-3"><span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#1e0178] text-xs font-semibold text-white">✓</span>AI-assisted decisions</li>
      </ul>
    </div>
    <div class="rounded-[2rem] bg-white p-8 shadow-soft ring-1 ring-slate-200">
      <p class="text-xs uppercase tracking-[0.3em] text-[#1e0178]">Suppliers</p>
      <h2 class="mt-4 text-2xl font-semibold text-slate-900">Make bidding effortless</h2>
      <ul class="mt-6 space-y-4 text-sm text-slate-600">
        <li class="flex gap-3"><span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#1e0178] text-xs font-semibold text-white">✓</span>Easy bidding</li>
        <li class="flex gap-3"><span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#1e0178] text-xs font-semibold text-white">✓</span>Opportunity discovery</li>
        <li class="flex gap-3"><span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#1e0178] text-xs font-semibold text-white">✓</span>Performance tracking</li>
      </ul>
    </div>
  </section>
</div>
<?php
renderGuestPageEnd();
