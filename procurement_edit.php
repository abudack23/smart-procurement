<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';
requireRole('admin');

$procurementId = (int)($_GET['id'] ?? 0);
$procurement = getProcurementById($procurementId);
if (!$procurement) {
    flash('Procurement not found.', 'error');
    header('Location: procurements.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budget = trim($_POST['budget'] ?? '');
    $delivery_days = (int)($_POST['delivery_days'] ?? 0);
    $deadline = trim($_POST['submission_deadline'] ?? '');
    $criteria = trim($_POST['evaluation_criteria'] ?? '');
    $status = in_array($_POST['status'] ?? 'open', ['open','closed'], true) ? $_POST['status'] : 'open';
    if ($title === '' || $description === '' || $delivery_days <= 0 || $deadline === '') {
        flash('Please provide title, description, deadline, and delivery timeline.', 'error');
    } else {
        $stmt = $pdo->prepare('UPDATE procurements SET title = ?, description = ?, budget = ?, delivery_days = ?, submission_deadline = ?, evaluation_criteria = ?, status = ? WHERE id = ?');
        $stmt->execute([$title, $description, $budget ?: null, $delivery_days, $deadline, $criteria ?: null, $status, $procurementId]);
        logAction($_SESSION['user_id'], 'Updated procurement', 'Procurement ID: ' . $procurementId);
        flash('Procurement updated successfully.');
        header('Location: procurements.php');
        exit;
    }
}

renderAuthPageStart('Edit Procurement', 'procurements');
?>
<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
  <h2 class="text-xl font-semibold text-slate-900">Edit procurement</h2>
  <p class="mt-2 text-sm text-slate-500">Update procurement details and status.</p>
  <form method="post" class="mt-6 space-y-5">
    <div>
      <label class="block text-sm font-medium text-slate-700">Title</label>
      <input type="text" name="title" required value="<?= htmlspecialchars($procurement['title']) ?>" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900" />
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700">Description</label>
      <textarea name="description" rows="4" required class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900"><?= htmlspecialchars($procurement['description']) ?></textarea>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label class="block text-sm font-medium text-slate-700">Budget</label>
        <input type="text" name="budget" value="<?= htmlspecialchars($procurement['budget']) ?>" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900" />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Delivery days</label>
        <input type="number" name="delivery_days" min="1" required value="<?= htmlspecialchars($procurement['delivery_days']) ?>" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900" />
      </div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label class="block text-sm font-medium text-slate-700">Submission deadline</label>
        <input type="date" name="submission_deadline" required value="<?= htmlspecialchars($procurement['submission_deadline']) ?>" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900" />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Status</label>
        <select name="status" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
          <option value="open" <?= $procurement['status'] === 'open' ? 'selected' : '' ?>>Open</option>
          <option value="closed" <?= $procurement['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
        </select>
      </div>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700">Evaluation criteria</label>
      <input type="text" name="evaluation_criteria" value="<?= htmlspecialchars($procurement['evaluation_criteria']) ?>" class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900" />
    </div>
    <button type="submit" class="w-full rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Save changes</button>
  </form>
</div>
<?php renderAuthPageEnd();
