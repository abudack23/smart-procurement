<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';
requireRole('supplier');
$user = currentUser();

$procurementId = (int)($_GET['procurement_id'] ?? 0);
$procurement = getProcurementById($procurementId);
if (!$procurement || $procurement['status'] !== 'open') {
    flash('The selected procurement is not available.', 'error');
    header('Location: opportunities.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM bids WHERE procurement_id = ? AND supplier_id = ? LIMIT 1');
$stmt->execute([$procurementId, $user['id']]);
$existingBid = $stmt->fetch();
$bidCount = (int)$pdo->query('SELECT COUNT(*) FROM bids WHERE procurement_id = ' . (int)$procurementId)->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price = trim($_POST['price'] ?? '');
    $delivery_days = (int)($_POST['delivery_days'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');
    $proposal = uploadFile($_FILES['proposal_document'] ?? null, 'uploads/');

    if ($price === '' || $delivery_days <= 0) {
        flash('Price and delivery days are required.', 'error');
    } else {
        if ($existingBid) {
            $stmt = $pdo->prepare('UPDATE bids SET price = ?, delivery_days = ?, remarks = ?, proposal_document = COALESCE(?, proposal_document), created_at = NOW() WHERE id = ?');
            $stmt->execute([$price, $delivery_days, $remarks ?: null, $proposal, $existingBid['id']]);
            logAction($user['id'], 'Updated bid', 'Bid ID: ' . $existingBid['id'] . ' Procurement ID: ' . $procurementId);
            notifyAdminUsers('Bid updated', 'Supplier ' . $user['name'] . ' updated a bid for ' . $procurement['title'] . '.');
            flash('Your bid has been updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO bids (procurement_id, supplier_id, price, delivery_days, remarks, proposal_document, status, created_at) VALUES (?, ?, ?, ?, ?, ?, "pending", NOW())');
            $stmt->execute([$procurementId, $user['id'], $price, $delivery_days, $remarks ?: null, $proposal]);
            $bidId = $pdo->lastInsertId();
            logAction($user['id'], 'Submitted bid', 'Bid ID: ' . $bidId . ' Procurement ID: ' . $procurementId);
            notifyAdminUsers('New bid submitted', 'Supplier ' . $user['name'] . ' submitted a bid for ' . $procurement['title'] . '.');
            flash('Your bid has been submitted successfully.');
        }
        header('Location: opportunities.php');
        exit;
    }
}

renderAuthPageStart('Submit Bid', 'submit_bid');
?>
<div class="grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
  <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-6 rounded-3xl border border-slate-200 bg-slate-50 p-5">
      <div class="flex flex-wrap items-center gap-2">
        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-200">Open opportunity</span>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"><?= $bidCount ?> bids submitted</span>
        <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">AI insight ready</span>
      </div>
      <h2 class="mt-4 text-xl font-semibold text-slate-900"><?= htmlspecialchars($procurement['title']) ?></h2>
      <p class="mt-2 text-sm text-slate-600"><?= htmlspecialchars($procurement['description']) ?></p>
      <div class="mt-4 flex flex-wrap gap-3 text-sm text-slate-600">
        <span class="rounded-full bg-white px-3 py-2 shadow-sm">Deadline: <?= date('M j, Y', strtotime($procurement['submission_deadline'])) ?></span>
        <span class="rounded-full bg-white px-3 py-2 shadow-sm">Budget: <?= htmlspecialchars($procurement['budget'] ?: 'TBD') ?></span>
        <span class="rounded-full bg-white px-3 py-2 shadow-sm">Delivery target: <?= (int)($procurement['delivery_days'] ?? 0) ?> days</span>
      </div>
    </div>
    <form action="submit_bid.php?procurement_id=<?= $procurement['id'] ?>" method="post" enctype="multipart/form-data" class="space-y-5">
      <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
        <label class="block text-sm font-semibold text-slate-900">Proposal price (PHP)</label>
        <p class="mt-1 text-xs text-slate-500">Keep your pricing aligned with budget expectations and market competitiveness.</p>
        <input type="number" step="0.01" name="price" required value="<?= htmlspecialchars($existingBid['price'] ?? '') ?>" class="mt-3 w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
      </div>
      <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
        <label class="block text-sm font-semibold text-slate-900">Delivery timeline (days)</label>
        <p class="mt-1 text-xs text-slate-500">Shorter delivery windows are often viewed as more competitive when quality is maintained.</p>
        <input type="number" name="delivery_days" min="1" required value="<?= htmlspecialchars($existingBid['delivery_days'] ?? '') ?>" class="mt-3 w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
      </div>
      <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
        <label class="block text-sm font-semibold text-slate-900">Remarks</label>
        <p class="mt-1 text-xs text-slate-500">Add your value proposition, delivery confidence, or special notes for the procurement team.</p>
        <textarea name="remarks" rows="4" class="mt-3 w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-200"><?= htmlspecialchars($existingBid['remarks'] ?? '') ?></textarea>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
        <label class="block text-sm font-semibold text-slate-900">Proposal document</label>
        <p class="mt-1 text-xs text-slate-500">Upload a PDF or document to support your bid submission.</p>
        <input type="file" name="proposal_document" class="mt-3 w-full text-sm text-slate-700" />
      </div>
      <button type="submit" class="w-full rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"><?= $existingBid ? 'Update bid' : 'Submit bid' ?></button>
    </form>
  </section>
  <aside class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-xl font-semibold text-slate-900">Bid intelligence</h2>
      <p class="mt-2 text-sm text-slate-500">Use these hints to strengthen your proposal before sending it.</p>
      <div class="mt-5 space-y-4">
        <article class="rounded-3xl border border-slate-200 bg-slate-50 p-4"> <p class="text-sm font-semibold text-slate-900">Competitive pricing</p><p class="mt-1 text-sm text-slate-600">Position your bid near the procurement budget while preserving margin.</p></article>
        <article class="rounded-3xl border border-slate-200 bg-slate-50 p-4"> <p class="text-sm font-semibold text-slate-900">Delivery confidence</p><p class="mt-1 text-sm text-slate-600">Shorter timelines help show responsiveness and planning discipline.</p></article>
        <article class="rounded-3xl border border-slate-200 bg-slate-50 p-4"> <p class="text-sm font-semibold text-slate-900">AI recommendation</p><p class="mt-1 text-sm text-slate-600">Use clear, concise remarks to highlight quality, speed, and value.</p></article>
      </div>
    </section>
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-xl font-semibold text-slate-900">Procurement summary</h2>
      <div class="mt-5 space-y-4 text-sm text-slate-600">
        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4"><p class="font-semibold text-slate-900">Status</p><p class="mt-2">Open</p></div>
        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4"><p class="font-semibold text-slate-900">Evaluation criteria</p><p class="mt-2"><?= htmlspecialchars($procurement['evaluation_criteria'] ?: 'Standard selection based on price, timeline and experience.') ?></p></div>
        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4"><p class="font-semibold text-slate-900">Submission window</p><p class="mt-2">Due <?= date('M j, Y', strtotime($procurement['submission_deadline'])) ?></p></div>
      </div>
    </section>
  </aside>
</div>
<?php
renderAuthPageEnd();
