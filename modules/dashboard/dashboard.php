<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../../auth/login.php'); exit; }
require_once '../../includes/db.php';

$isAdmin = $_SESSION['role'] === 'admin';

$totalDecl = (int)$pdo->query('SELECT COUNT(*) FROM asset_declarations')->fetchColumn();
$totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalPols = (int)$pdo->query('SELECT COUNT(*) FROM politicians')->fetchColumn();

$myDecls = [];
$politician = null;
if (!$isAdmin) {
    $stmt = $pdo->prepare('SELECT p.* FROM politicians p WHERE p.user_id = :uid');
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $politician = $stmt->fetch();
    if ($politician) {
        $stmt = $pdo->prepare(
            'SELECT year, status, total_deposits, total_debts, submitted_at
               FROM asset_declarations
              WHERE politician_id = :pid
           ORDER BY year DESC LIMIT 5'
        );
        $stmt->execute([':pid' => $politician['id']]);
        $myDecls = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard &ndash; CEI326</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Dashboard</h1>
      <p class="subtitle">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?> &nbsp;&middot;&nbsp; <?= $isAdmin ? 'Administrator' : 'Politician' ?></p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../../index.php" class="nav-link">Home</a>
    <a href="dashboard.php" class="nav-link active">Dashboard</a>
    <?php if ($isAdmin): ?><a href="../admin/admin_dashboard.php" class="nav-link">Admin</a><?php endif; ?>
    <a href="../submit/submit_dashboard.php" class="nav-link">Submit</a>
    <a href="../search/search_dashboard.php" class="nav-link">Search</a>
    <a href="../search/statistics.php" class="nav-link">Statistics</a>
    <a href="../../auth/logout.php" class="nav-link nav-logout">Logout</a>
  </nav>
  <main>
    <section id="stats-section">
      <h2>Overview</h2>
      <div class="panel-grid">
        <?php if ($isAdmin): ?>
          <div class="panel"><h2>Total Users</h2><p class="stat-number"><?= $totalUsers ?></p><p class="stat-label">registered accounts</p></div>
          <div class="panel"><h2>Politicians</h2><p class="stat-number"><?= $totalPols ?></p><p class="stat-label">profiles in system</p></div>
          <div class="panel"><h2>Declarations</h2><p class="stat-number"><?= $totalDecl ?></p><p class="stat-label">total asset declarations</p></div>
        <?php else: ?>
          <div class="panel"><h2>My Declarations</h2><p class="stat-number"><?= count($myDecls) ?></p><p class="stat-label">submitted declarations</p></div>
          <div class="panel"><h2>System Total</h2><p class="stat-number"><?= $totalDecl ?></p><p class="stat-label">declarations in system</p></div>
        <?php endif; ?>
      </div>
    </section>

    <?php if (!$isAdmin && !empty($myDecls)): ?>
    <section id="recent-section">
      <h2>My Recent Declarations</h2>
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Year</th><th>Status</th><th>Deposits</th><th>Debts</th><th>Submitted</th></tr></thead>
          <tbody>
            <?php foreach ($myDecls as $d): ?>
            <tr>
              <td><?= (int)$d['year'] ?></td>
              <td>
                <span class="badge <?= $d['status'] === 'submitted' ? 'badge-green' : 'badge-orange' ?>">
                  <?= ucfirst(htmlspecialchars($d['status'])) ?>
                </span>
              </td>
              <td>&euro;<?= number_format((float)$d['total_deposits'], 2) ?></td>
              <td>&euro;<?= number_format((float)$d['total_debts'], 2) ?></td>
              <td><?= $d['submitted_at'] ? date('d M Y', strtotime($d['submitted_at'])) : '–' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php elseif (!$isAdmin && empty($myDecls)): ?>
    <section id="recent-section">
      <h2>My Declarations</h2>
      <p>You haven't submitted any declarations yet. <a href="../submit/my_submissions.php">Submit your first declaration →</a></p>
    </section>
    <?php endif; ?>

    <section id="actions-section">
      <h2>Quick Actions</h2>
      <div class="panel-grid">
        <?php if (!$isAdmin): ?>
        <div class="panel">
          <h2>My Profile</h2>
          <p class="stat-label">Update your personal information</p>
          <a href="../submit/my_profile.php" class="btn panel-action">My Profile</a>
        </div>
        <div class="panel">
          <h2>My Submissions</h2>
          <p class="stat-label">Manage your asset declarations</p>
          <a href="../submit/my_submissions.php" class="btn panel-action">My Submissions</a>
        </div>
        <?php endif; ?>
        <div class="panel">
          <h2>Search</h2>
          <p class="stat-label">Search through existing records</p>
          <a href="../search/search_dashboard.php" class="btn panel-action">Search Records</a>
        </div>
        <div class="panel">
          <h2>Statistics</h2>
          <p class="stat-label">View system analytics and charts</p>
          <a href="../search/statistics.php" class="btn panel-action">View Statistics</a>
        </div>
        <?php if ($isAdmin): ?>
        <div class="panel">
          <h2>Admin Panel</h2>
          <p class="stat-label">Manage users and system data</p>
          <a href="../admin/admin_dashboard.php" class="btn btn-danger panel-action">Open Admin</a>
        </div>
        <?php endif; ?>
      </div>
    </section>
  </main>
  <footer id="page-footer"><p>CEI326 Web Engineering 2026 &ndash; Dashboard</p></footer>
</body>
</html>
