<?php
// Session
session_start();

// Access control
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

// Dependencies
require_once '../../includes/db.php';

// Load dashboard
$stmt = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE user_id = :uid');
$stmt->execute([':uid' => $_SESSION['user_id']]);
$myPostCount = (int) $stmt->fetchColumn();

$totalPosts = (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

$stmt = $pdo->prepare(
    'SELECT title, category, created_at FROM posts
     WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5'
);
$stmt->execute([':uid' => $_SESSION['user_id']]);
$myLatest = $stmt->fetchAll();

$isAdmin = $_SESSION['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – CEI326</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Dashboard</h1>
      <p class="subtitle">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?> &nbsp;·&nbsp; <?= $isAdmin ? 'Administrator' : 'User' ?></p>
    </div>
  </header>

  <nav id="main-nav">
    <a href="../../index.php" class="nav-link">Home</a>
    <a href="dashboard.php" class="nav-link active">Dashboard</a>
    <?php if ($isAdmin): ?>
    <a href="../admin/admin_dashboard.php" class="nav-link">Admin</a>
    <?php endif; ?>
    <a href="../submit/submit_dashboard.php" class="nav-link">Submit</a>
    <a href="../search/search_dashboard.php" class="nav-link">Search</a>
    <a href="../../auth/logout.php" class="nav-link nav-logout">Logout</a>
  </nav>

  <main>

    <section id="stats-section">
      <h2>Overview</h2>
      <div class="panel-grid">
        <div class="panel">
          <h2>My Posts</h2>
          <p class="stat-number"><?= $myPostCount ?></p>
          <p class="stat-label">submitted by me</p>
        </div>
        <div class="panel">
          <h2>Total Posts</h2>
          <p class="stat-number"><?= $totalPosts ?></p>
          <p class="stat-label">in the system</p>
        </div>
        <?php if ($isAdmin): ?>
        <div class="panel">
          <h2>Total Users</h2>
          <p class="stat-number"><?= $totalUsers ?></p>
          <p class="stat-label">registered accounts</p>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <section id="recent-section">
      <h2>My Recent Posts</h2>
      <?php if (empty($myLatest)): ?>
        <p>You haven't submitted any posts yet. <a href="../submit/submit_dashboard.php">Submit your first entry →</a></p>
      <?php else: ?>
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($myLatest as $p): ?>
              <tr>
                <td><?= htmlspecialchars($p['title']) ?></td>
                <td>
                  <span class="badge <?=
                    $p['category'] === 'report'
                      ? 'badge-category-report'
                      : ($p['category'] === 'project' ? 'badge-category-project' : 'badge-category-other')
                  ?>">
                    <?= htmlspecialchars(ucfirst($p['category'])) ?>
                  </span>
                </td>
                <td><?= htmlspecialchars(date('d M Y', strtotime($p['created_at']))) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <section id="actions-section">
      <h2>Quick Actions</h2>
      <div class="panel-grid">
        <div class="panel">
          <h2>All Posts</h2>
          <p class="stat-label">Browse every entry in the system</p>
          <a href="../list/list.php" class="btn panel-action">Browse Posts</a>
        </div>
        <div class="panel">
          <h2>New Submission</h2>
          <p class="stat-label">Add a new record to the system</p>
          <a href="../submit/submit_dashboard.php" class="btn panel-action">Submit Entry</a>
        </div>
        <div class="panel">
          <h2>Search</h2>
          <p class="stat-label">Search through existing records quickly</p>
          <a href="../search/search_dashboard.php" class="btn panel-action">Search Records</a>
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

  <footer id="page-footer">
    <p>CEI326 Web Engineering 2026 – Dashboard</p>
  </footer>

</body>
</html>
