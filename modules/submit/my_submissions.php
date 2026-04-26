<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../../auth/login.php'); exit; }
require_once '../../includes/db.php';

// Asset declarations are filed by politicians only. Admins do not own
// declarations, so redirect them to the admin submissions screen which
// is the equivalent management surface for their role.
if ($_SESSION['role'] === 'admin') {
    header('Location: ../admin/manage_submissions.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM politicians WHERE user_id = :uid');
$stmt->execute([':uid' => $_SESSION['user_id']]);
$politician = $stmt->fetch();

// Politicians without a profile yet are sent to fill it in first.
if (!$politician) {
    header('Location: my_profile.php?setup=1');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save' || $action === 'submit_final') {
        $year = (int)($_POST['year'] ?? date('Y'));
        $total_deposits = (float)str_replace(',', '', $_POST['total_deposits'] ?? '0');
        $total_debts = (float)str_replace(',', '', $_POST['total_debts'] ?? '0');
        $annual_income = (float)str_replace(',', '', $_POST['annual_income'] ?? '0');
        $vehicles_count = (int) ($_POST['vehicles_count'] ?? 0);
        $vehicles_details = trim($_POST['vehicles_details'] ?? '');
        $shares_value = (float)str_replace(',', '', $_POST['shares_value'] ?? '0');
        $shares_details = trim($_POST['shares_details'] ?? '');
        $real_estate_count = (int) ($_POST['real_estate_count'] ?? 0);
        $real_estate_details = trim($_POST['real_estate_details'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($year < 1990 || $year > (int)date('Y')) {
            $errors[] = 'Invalid year.';
        }

        if (empty($errors)) {
            $status = $action === 'submit_final' ? 'submitted' : 'draft';
            $submitted_at = $action === 'submit_final' ? date('Y-m-d H:i:s') : null;

            // Only one declaration per (politician, year) is allowed by
            // the schema, so update an existing row when present and
            // insert a new one otherwise.
            $existing = $pdo->prepare(
                'SELECT id FROM asset_declarations WHERE politician_id = :pid AND year = :yr'
            );
            $existing->execute([':pid' => $politician['id'], ':yr' => $year]);
            $existingId = $existing->fetchColumn();

            if ($existingId) {
                $pdo->prepare(
                    'UPDATE asset_declarations
                        SET status = :st,
                            total_deposits = :dep,
                            total_debts = :deb,
                            annual_income = :inc,
                            vehicles_count = :vc,
                            vehicles_details = :vd,
                            shares_value = :sv,
                            shares_details = :sd,
                            real_estate_count = :rec,
                            real_estate_details = :red,
                            notes = :no,
                            submitted_at = COALESCE(:sat, submitted_at)
                      WHERE id = :id'
                )->execute([
                    ':st' => $status,
                    ':dep' => $total_deposits, ':deb' => $total_debts, ':inc' => $annual_income,
                    ':vc' => $vehicles_count, ':vd' => $vehicles_details,
                    ':sv' => $shares_value, ':sd' => $shares_details,
                    ':rec' => $real_estate_count, ':red' => $real_estate_details,
                    ':no' => $notes,
                    ':sat' => $submitted_at,
                    ':id' => $existingId,
                ]);
            } else {
                $pdo->prepare(
                    'INSERT INTO asset_declarations
                        (politician_id, year, status, total_deposits, total_debts, annual_income,
                         vehicles_count, vehicles_details, shares_value, shares_details,
                         real_estate_count, real_estate_details, notes, submitted_at)
                     VALUES
                        (:pid, :yr, :st, :dep, :deb, :inc,
                         :vc, :vd, :sv, :sd,
                         :rec, :red, :no, :sat)'
                )->execute([
                    ':pid' => $politician['id'], ':yr' => $year, ':st' => $status,
                    ':dep' => $total_deposits, ':deb' => $total_debts, ':inc' => $annual_income,
                    ':vc' => $vehicles_count, ':vd' => $vehicles_details,
                    ':sv' => $shares_value, ':sd' => $shares_details,
                    ':rec' => $real_estate_count, ':red' => $real_estate_details,
                    ':no' => $notes, ':sat' => $submitted_at,
                ]);
            }
            $success = $action === 'submit_final'
                ? 'Declaration submitted officially!'
                : 'Draft saved successfully.';
        }
    }
}

$declStmt = $pdo->prepare(
    'SELECT * FROM asset_declarations WHERE politician_id = :pid ORDER BY year DESC'
);
$declStmt->execute([':pid' => $politician['id']]);
$decls = $declStmt->fetchAll();

// If ?edit=N was supplied, load that declaration for editing (only
// when it really belongs to the current politician).
$editing = null;
$editId = (int)($_GET['edit'] ?? 0);
if ($editId) {
    $editStmt = $pdo->prepare(
        'SELECT * FROM asset_declarations WHERE id = :id AND politician_id = :pid'
    );
    $editStmt->execute([':id' => $editId, ':pid' => $politician['id']]);
    $editing = $editStmt->fetch() ?: null;
}

$alreadySubmitted = $editing && $editing['status'] === 'submitted';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Submissions &ndash; CEI326</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>My Submissions</h1>
      <p class="subtitle">Asset Declarations &ndash; Pothen Esches</p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../../index.php" class="nav-link">Home</a>
    <a href="../dashboard/dashboard.php" class="nav-link">Dashboard</a>
    <a href="submit_dashboard.php" class="nav-link">Submit</a>
    <a href="my_profile.php" class="nav-link">My Profile</a>
    <a href="my_submissions.php" class="nav-link active">My Submissions</a>
    <a href="../search/search_dashboard.php" class="nav-link">Search</a>
    <a href="../search/statistics.php" class="nav-link">Statistics</a>
    <a href="../../auth/logout.php" class="nav-link nav-logout">Logout</a>
  </nav>
  <main>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <section id="form-section">
      <h2><?= $editing ? 'Edit Declaration &ndash; ' . (int)$editing['year'] : 'New Asset Declaration' ?></h2>
      <div class="form-card">
        <form method="POST" action="my_submissions.php<?= $editing ? '?edit=' . (int)$editing['id'] : '' ?>" id="declaration-form">

          <div class="form-group">
            <label>Declaration Year<span style="color:#c0392b;">*</span></label>
            <select name="year" class="form-control" <?= $alreadySubmitted ? 'disabled' : '' ?>>
              <?php for ($y = (int)date('Y'); $y >= 2000; $y--): ?>
                <option value="<?= $y ?>" <?= ($editing['year'] ?? (int)date('Y')) == $y ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
            <?php if ($alreadySubmitted): ?>
              <input type="hidden" name="year" value="<?= (int)$editing['year'] ?>">
            <?php endif; ?>
          </div>

          <h3 style="font-size:1rem;color:#1a4a35;margin:20px 0 12px;border-bottom:2px solid #27ae60;padding-bottom:6px;display:inline-block;">Financial Assets</h3>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
            <div class="form-group">
              <label>Total Deposits (&euro;)</label>
              <input type="number" name="total_deposits" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars((string)($editing['total_deposits'] ?? '0')) ?>">
            </div>
            <div class="form-group">
              <label>Total Debts (&euro;)</label>
              <input type="number" name="total_debts" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars((string)($editing['total_debts'] ?? '0')) ?>">
            </div>
            <div class="form-group">
              <label>Annual Income (&euro;)</label>
              <input type="number" name="annual_income" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars((string)($editing['annual_income'] ?? '0')) ?>">
            </div>
          </div>

          <h3 style="font-size:1rem;color:#1a4a35;margin:20px 0 12px;border-bottom:2px solid #27ae60;padding-bottom:6px;display:inline-block;">Vehicles</h3>
          <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px;">
            <div class="form-group">
              <label>Number of Vehicles</label>
              <input type="number" name="vehicles_count" class="form-control" min="0" value="<?= (int)($editing['vehicles_count'] ?? 0) ?>">
            </div>
            <div class="form-group">
              <label>Vehicle Details</label>
              <input type="text" name="vehicles_details" class="form-control" placeholder="e.g. Toyota Corolla 2019, Honda Civic 2017" value="<?= htmlspecialchars($editing['vehicles_details'] ?? '') ?>">
            </div>
          </div>

          <h3 style="font-size:1rem;color:#1a4a35;margin:20px 0 12px;border-bottom:2px solid #27ae60;padding-bottom:6px;display:inline-block;">Shares &amp; Investments</h3>
          <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px;">
            <div class="form-group">
              <label>Shares Value (&euro;)</label>
              <input type="number" name="shares_value" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars((string)($editing['shares_value'] ?? '0')) ?>">
            </div>
            <div class="form-group">
              <label>Shares Details</label>
              <input type="text" name="shares_details" class="form-control" placeholder="e.g. Bank of Cyprus shares" value="<?= htmlspecialchars($editing['shares_details'] ?? '') ?>">
            </div>
          </div>

          <h3 style="font-size:1rem;color:#1a4a35;margin:20px 0 12px;border-bottom:2px solid #27ae60;padding-bottom:6px;display:inline-block;">Real Estate</h3>
          <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px;">
            <div class="form-group">
              <label>Number of Properties</label>
              <input type="number" name="real_estate_count" class="form-control" min="0" value="<?= (int)($editing['real_estate_count'] ?? 0) ?>">
            </div>
            <div class="form-group">
              <label>Property Details</label>
              <input type="text" name="real_estate_details" class="form-control" placeholder="e.g. Apartment in Nicosia" value="<?= htmlspecialchars($editing['real_estate_details'] ?? '') ?>">
            </div>
          </div>

          <div class="form-group">
            <label>Additional Notes</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Any other information..."><?= htmlspecialchars($editing['notes'] ?? '') ?></textarea>
          </div>

          <?php if ($alreadySubmitted): ?>
            <div class="info-panel" style="margin-bottom:16px;">
              <span class="status-submitted">✓ Officially Submitted on <?= date('d M Y', strtotime($editing['submitted_at'])) ?></span>
              <p style="margin-top:8px;font-size:0.9rem;">This declaration has been officially submitted and cannot be re-submitted. You may still save updates as a draft.</p>
            </div>
          <?php endif; ?>

          <div class="form-actions">
            <button type="submit" name="action" value="save" class="btn btn-secondary">Save as Draft</button>
            <?php if (!$alreadySubmitted): ?>
              <button type="submit" name="action" value="submit_final" class="btn btn-primary" id="btn-submit-final">Submit Officially</button>
            <?php endif; ?>
            <a href="my_submissions.php" class="btn btn-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </section>

    <section id="history-section">
      <h2>My Declaration History</h2>
      <?php if (empty($decls)): ?>
        <div class="info-panel"><p>No declarations yet. Use the form above to create your first asset declaration.</p></div>
      <?php else: ?>
        <div class="card-list">
          <?php foreach ($decls as $d): ?>
          <div class="card" style="flex-direction:column;align-items:flex-start;gap:8px;">
            <div style="display:flex;align-items:center;gap:16px;width:100%;">
              <h2 style="flex:1;color:#1a4a35;font-size:1.06rem;border:none;padding:0;margin:0;">Declaration <?= (int)$d['year'] ?></h2>
              <span class="status-<?= htmlspecialchars($d['status']) ?>"><?= ucfirst(htmlspecialchars($d['status'])) ?></span>
              <a href="my_submissions.php?edit=<?= (int)$d['id'] ?>" class="btn btn-secondary" style="padding:6px 14px;font-size:0.85rem;">Edit</a>
            </div>
            <div style="display:flex;gap:24px;font-size:0.88rem;color:#666;flex-wrap:wrap;">
              <span>💰 Deposits: <strong>&euro;<?= number_format((float)$d['total_deposits'], 2) ?></strong></span>
              <span>📉 Debts: <strong>&euro;<?= number_format((float)$d['total_debts'], 2) ?></strong></span>
              <span>🚗 Vehicles: <strong><?= (int)$d['vehicles_count'] ?></strong></span>
              <span>🏠 Properties: <strong><?= (int)$d['real_estate_count'] ?></strong></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
  <footer id="page-footer"><p>CEI326 Web Engineering 2026 &mdash; Submit Module</p></footer>
  <script>
    // The "Submit Officially" button only renders for editable rows, so
    // skip wiring up the confirmation handler when it isn't on the page.
    var submitFinalBtn = document.getElementById('btn-submit-final');
    if (submitFinalBtn) {
      submitFinalBtn.addEventListener('click', function (e) {
        e.preventDefault();
        var form = this.closest('form');
        Swal.fire({
          title: 'Submit Officially?',
          text: 'This action marks the declaration as submitted and cannot be undone.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#27ae60',
          cancelButtonColor: '#c0392b',
          confirmButtonText: 'Yes, submit it!',
          cancelButtonText: 'Cancel'
        }).then(function (result) {
          if (result.isConfirmed) {
            // Inject the action explicitly because the click handler
            // pre-empted the default name=action submit value.
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'action';
            input.value = 'submit_final';
            form.appendChild(input);
            form.submit();
          }
        });
      });
    }
  </script>
</body>
</html>
