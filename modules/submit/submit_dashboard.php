<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../../auth/login.php'); exit; }
require_once '../../includes/db.php';
$isAdmin = $_SESSION['role'] === 'admin';

// Politicians get an extra info card with their party / position; for
// admin accounts this lookup is skipped (admins have no politician row).
$politician = null;
if (!$isAdmin) {
    $stmt = $pdo->prepare(
        'SELECT p.*, pa.name AS party_name, po.title AS position_title
           FROM politicians p
      LEFT JOIN parties pa ON pa.id = p.party_id
      LEFT JOIN positions po ON po.id = p.position_id
          WHERE p.user_id = :uid'
    );
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $politician = $stmt->fetch();
}

$declCount = 0;
if ($politician) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM asset_declarations WHERE politician_id = :pid');
    $stmt->execute([':pid' => $politician['id']]);
    $declCount = (int)$stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Submit Module &ndash; CEI326</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Submit Module</h1>
      <p class="subtitle">Asset Declaration Portal &nbsp;&middot;&nbsp; <?= htmlspecialchars($_SESSION['username']) ?></p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../../index.php" class="nav-link">Home</a>
    <a href="../dashboard/dashboard.php" class="nav-link">Dashboard</a>
    <?php if ($isAdmin): ?><a href="../admin/admin_dashboard.php" class="nav-link">Admin</a><?php endif; ?>
    <a href="submit_dashboard.php" class="nav-link active">Submit</a>
    <a href="../search/search_dashboard.php" class="nav-link">Search</a>
    <a href="../search/statistics.php" class="nav-link">Statistics</a>
    <a href="../../auth/logout.php" class="nav-link nav-logout">Logout</a>
  </nav>
  <main>

    <?php if (!$isAdmin): ?>
    <section id="stats-section">
      <h2>Overview</h2>
      <div class="panel-grid">
        <div class="panel">
          <h2>My Declarations</h2>
          <p class="stat-number"><?= $declCount ?></p>
          <p class="stat-label">submitted declarations</p>
        </div>
        <?php if ($politician): ?>
        <div class="panel">
          <h2>Party</h2>
          <p class="stat-number" style="font-size:1.4rem;"><?= htmlspecialchars($politician['party_name'] ?? '–') ?></p>
          <p class="stat-label">affiliated party</p>
        </div>
        <div class="panel">
          <h2>Position</h2>
          <p class="stat-number" style="font-size:1.2rem;"><?= htmlspecialchars($politician['position_title'] ?? '–') ?></p>
          <p class="stat-label">current position</p>
        </div>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>

    <section id="modules-section">
      <h2>Your Modules</h2>
      <div class="submit-icon-grid">
        <a href="my_profile.php" class="submit-icon-card">
          <div class="submit-icon-wrap">👤</div>
          <strong>My Profile</strong>
          <span>View and update your personal information</span>
        </a>
        <?php if (!$isAdmin): ?>
        <a href="my_submissions.php" class="submit-icon-card">
          <div class="submit-icon-wrap">📄</div>
          <strong>My Submissions</strong>
          <span>Manage your asset declarations (Pothen Esches)</span>
        </a>
        <?php endif; ?>
      </div>
    </section>

  </main>
  <footer id="page-footer"><p>CEI326 Web Engineering 2026 &mdash; Submit Module</p></footer>
</body>
</html>
