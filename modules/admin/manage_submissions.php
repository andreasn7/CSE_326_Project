<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../../auth/login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { header('Location: ../dashboard/dashboard.php'); exit; }
require_once '../../includes/db.php';

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = (int)($_POST['decl_id'] ?? 0);
        if ($id) {
            $pdo->prepare('DELETE FROM asset_declarations WHERE id = :id')->execute([':id' => $id]);
            $success = 'Declaration deleted.';
        }
    }
}

$filterYear = (int)($_GET['year'] ?? 0);
$filterParty = (int)($_GET['party'] ?? 0);
$filterStatus = $_GET['status'] ?? '';
$keyword = trim($_GET['keyword'] ?? '');

// Names are joined from the users table because identity fields live
// on `users` and the `politicians` table holds only role-specific data.
$sql = 'SELECT ad.*, u.first_name, u.last_name, pa.name AS party_name,
               po.title AS position_title, u.email
          FROM asset_declarations ad
          JOIN politicians p ON p.id = ad.politician_id
          JOIN users u ON u.id = p.user_id
     LEFT JOIN parties pa ON pa.id = p.party_id
     LEFT JOIN positions po ON po.id = p.position_id
         WHERE 1=1';
$params = [];

if ($filterYear) { $sql .= ' AND ad.year = :yr'; $params[':yr'] = $filterYear; }
if ($filterParty) { $sql .= ' AND p.party_id = :pid'; $params[':pid'] = $filterParty; }
if ($filterStatus === 'submitted' || $filterStatus === 'draft') {
    $sql .= ' AND ad.status = :st';
    $params[':st'] = $filterStatus;
}
if ($keyword !== '') {
    $sql .= ' AND (u.first_name LIKE :kw1 OR u.last_name LIKE :kw2 OR pa.name LIKE :kw3)';
    $params[':kw1'] = '%' . $keyword . '%';
    $params[':kw2'] = '%' . $keyword . '%';
    $params[':kw3'] = '%' . $keyword . '%';
}
$sql .= ' ORDER BY ad.year DESC, u.last_name ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$declarations = $stmt->fetchAll();

$parties = $pdo->query('SELECT id, name FROM parties ORDER BY name')->fetchAll();
$years = $pdo->query('SELECT DISTINCT year FROM asset_declarations ORDER BY year DESC')
               ->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Submissions &ndash; Admin</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Manage Submissions</h1>
      <p class="subtitle">All Asset Declaration Submissions</p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../../index.php" class="nav-link">Home</a>
    <a href="../dashboard/dashboard.php" class="nav-link">Dashboard</a>
    <a href="admin_dashboard.php" class="nav-link">Admin</a>
    <a href="manage_users.php" class="nav-link">Manage Users</a>
    <a href="manage_submissions.php" class="nav-link active">Submissions</a>
    <a href="configure_system.php" class="nav-link">Configure</a>
    <a href="reports.php" class="nav-link">Reports</a>
    <a href="../search/search_dashboard.php" class="nav-link">Search</a>
    <a href="../search/statistics.php" class="nav-link">Statistics</a>
    <a href="../../auth/logout.php" class="nav-link nav-logout">Logout</a>
  </nav>
  <main>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <section id="filter-section">
      <h2>Filter Submissions</h2>
      <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin:0;">
          <label style="font-size:0.85rem;">Keyword</label>
          <input type="text" name="keyword" class="form-control" style="min-width:180px;" value="<?= htmlspecialchars($keyword) ?>" placeholder="Name or party...">
        </div>
        <div class="form-group" style="margin:0;">
          <label style="font-size:0.85rem;">Year</label>
          <select name="year" class="form-control">
            <option value="">All Years</option>
            <?php foreach ($years as $y): ?>
              <option value="<?= (int)$y ?>" <?= $filterYear == $y ? 'selected' : '' ?>><?= (int)$y ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin:0;">
          <label style="font-size:0.85rem;">Party</label>
          <select name="party" class="form-control">
            <option value="">All Parties</option>
            <?php foreach ($parties as $pt): ?>
              <option value="<?= (int)$pt['id'] ?>" <?= $filterParty == $pt['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pt['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin:0;">
          <label style="font-size:0.85rem;">Status</label>
          <select name="status" class="form-control">
            <option value="">All</option>
            <option value="submitted" <?= $filterStatus === 'submitted' ? 'selected' : '' ?>>Submitted</option>
            <option value="draft" <?= $filterStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
          </select>
        </div>
        <div style="display:flex;gap:8px;padding-top:24px;">
          <button type="submit" class="btn">Filter</button>
          <a href="manage_submissions.php" class="btn btn-secondary">Clear</a>
        </div>
      </form>
    </section>

    <section id="list-section">
      <h2>Declarations <small style="font-size:0.85rem;font-weight:400;color:#888;">(<?= count($declarations) ?> found)</small></h2>
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr><th>ID</th><th>Politician</th><th>Party</th><th>Year</th><th>Deposits</th><th>Debts</th><th>Status</th><th>Submitted At</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($declarations as $d): ?>
            <tr>
              <td><?= (int)$d['id'] ?></td>
              <td><?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?></td>
              <td><?= htmlspecialchars($d['party_name'] ?? '–') ?></td>
              <td><?= (int)$d['year'] ?></td>
              <td>&euro;<?= number_format((float)$d['total_deposits'], 2) ?></td>
              <td>&euro;<?= number_format((float)$d['total_debts'], 2) ?></td>
              <td><span class="badge badge-<?= htmlspecialchars($d['status']) ?>"><?= ucfirst(htmlspecialchars($d['status'])) ?></span></td>
              <td><?= $d['submitted_at'] ? date('d M Y', strtotime($d['submitted_at'])) : '–' ?></td>
              <td>
                <form method="POST" style="display:inline;" class="delete-form">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="decl_id" value="<?= (int)$d['id'] ?>">
                  <button type="submit" class="btn btn-danger" style="padding:6px 12px;font-size:0.82rem;">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($declarations)): ?>
              <tr><td colspan="9" style="text-align:center;color:#888;padding:24px;">No declarations found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
  <footer id="page-footer"><p>CEI326 Web Engineering 2026 &mdash; Admin Module</p></footer>
  <script>
  document.querySelectorAll('.delete-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      Swal.fire({
        title: 'Delete this declaration?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#c0392b',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) form.submit();
      });
    });
  });
</script>
</body>
</html>