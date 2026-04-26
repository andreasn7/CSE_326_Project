<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../../auth/login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { header('Location: ../dashboard/dashboard.php'); exit; }
require_once '../../includes/db.php';

$totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalPoliticians = (int)$pdo->query('SELECT COUNT(*) FROM politicians')->fetchColumn();
$totalDeclarations = (int)$pdo->query('SELECT COUNT(*) FROM asset_declarations')->fetchColumn();
$submittedDecl = (int)$pdo->query("SELECT COUNT(*) FROM asset_declarations WHERE status='submitted'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard &ndash; CEI326</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Admin Dashboard</h1>
      <p class="subtitle">System Administration Panel &nbsp;&middot;&nbsp; Logged in as <?= htmlspecialchars($_SESSION['username']) ?></p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../../index.php" class="nav-link">Home</a>
    <a href="../dashboard/dashboard.php" class="nav-link">Dashboard</a>
    <a href="admin_dashboard.php" class="nav-link active">Admin</a>
    <a href="manage_users.php" class="nav-link">Manage Users</a>
    <a href="manage_submissions.php" class="nav-link">Submissions</a>
    <a href="configure_system.php" class="nav-link">Configure</a>
    <a href="reports.php" class="nav-link">Reports</a>
    <a href="../search/search_dashboard.php" class="nav-link">Search</a>
    <a href="../search/statistics.php" class="nav-link">Statistics</a>
    <a href="../../auth/logout.php" class="nav-link nav-logout">Logout</a>
  </nav>
  <main>
    <section id="stats-section">
      <h2>System Overview</h2>
      <div class="panel-grid">
        <div class="panel">
          <h2>Total Users</h2>
          <p class="stat-number"><?= $totalUsers ?></p>
          <p class="stat-label">registered accounts</p>
        </div>
        <div class="panel">
          <h2>Politicians</h2>
          <p class="stat-number"><?= $totalPoliticians ?></p>
          <p class="stat-label">profiles created</p>
        </div>
        <div class="panel">
          <h2>Declarations</h2>
          <p class="stat-number"><?= $totalDeclarations ?></p>
          <p class="stat-label">total asset declarations</p>
        </div>
        <div class="panel">
          <h2>Submitted</h2>
          <p class="stat-number"><?= $submittedDecl ?></p>
          <p class="stat-label">officially submitted</p>
        </div>
      </div>
    </section>

    <section id="modules-section">
      <h2>Administration Modules</h2>
      <div class="admin-icon-grid">
        <a href="manage_users.php" class="admin-icon-card">
          <div class="admin-icon-wrap">👥</div>
          <strong>Manage Users</strong>
          <span>Add, edit or remove user accounts</span>
        </a>
        <a href="manage_submissions.php" class="admin-icon-card">
          <div class="admin-icon-wrap">📋</div>
          <strong>Manage Submissions</strong>
          <span>View and manage all asset declarations</span>
        </a>
        <a href="configure_system.php" class="admin-icon-card">
          <div class="admin-icon-wrap">⚙️</div>
          <strong>Configure System</strong>
          <span>Parties, positions, districts and committees</span>
        </a>
        <a href="reports.php" class="admin-icon-card">
          <div class="admin-icon-wrap">📊</div>
          <strong>Reports</strong>
          <span>Statistics and visual analytics</span>
        </a>
      </div>
    </section>

    <section id="profile-section">
      <h2>My Account</h2>
      <div class="change-pw-row">
        <div class="change-pw-info">
          <span class="change-pw-icon">🔒</span>
          <div>
            <strong>Change Password</strong>
            <p>Update your account password to keep your account secure.</p>
          </div>
        </div>
        <a href="change_password.php" class="btn btn-primary">Change Password</a>
      </div>
    </section>
  </main>
  <footer id="page-footer">
    <p>CEI326 Web Engineering 2026 &mdash; Admin Module</p>
  </footer>
</body>
</html>