<?php
// Session
session_start();

// Access control
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../../modules/dashboard.php');
    exit;
}

// Dependencies
require_once '../../includes/db.php';

// Load dashboard
$users = $pdo->query('SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
$totalPosts = $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Admin Dashboard</h1>
      <p class="subtitle">System Administration Panel &nbsp;·&nbsp; Logged in as <?= htmlspecialchars($_SESSION['username']) ?></p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../../index.php" class="nav-link">Home</a>
    <a href="../dashboard/dashboard.php" class="nav-link">Dashboard</a>
    <a href="admin_dashboard.php" class="nav-link active">Admin</a>
    <a href="../submit/submit_dashboard.php" class="nav-link">Submit</a>
    <a href="../search/search_dashboard.php" class="nav-link">Search</a>
    <a href="../../auth/logout.php" class="nav-link nav-logout">Logout</a>
  </nav>
  <main>
    <section id="stats-section">
      <h2>System Overview</h2>
      <div class="panel-grid">
        <div class="panel">
          <h2>Total Users</h2>
          <p class="stat-number"><?= htmlspecialchars((string) count($users)) ?></p>
          <p class="stat-label">registered accounts</p>
        </div>
        <div class="panel">
          <h2>Total Posts</h2>
          <p class="stat-number"><?= htmlspecialchars((string) $totalPosts) ?></p>
          <p class="stat-label">submitted entries</p>
        </div>
      </div>
    </section>
    <section id="users-section">
      <h2>Registered Users</h2>
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Registered</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td><?= htmlspecialchars((string) $u['id']) ?></td>
              <td><?= htmlspecialchars($u['username']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td>
                <span class="badge <?= $u['role'] === 'admin' ? 'badge-role-admin' : 'badge-role-user' ?>">
                  <?= htmlspecialchars($u['role']) ?>
                </span>
              </td>
              <td><?= htmlspecialchars(date('d M Y', strtotime($u['created_at']))) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
  <footer id="page-footer">
    <p>CEI326 Web Engineering 2026 - Admin Module</p>
  </footer>
</body>
</html>
