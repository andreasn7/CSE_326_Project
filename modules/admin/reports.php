<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../../auth/login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { header('Location: ../dashboard/dashboard.php'); exit; }
require_once '../../includes/db.php';

$totalDecl = (int)$pdo->query('SELECT COUNT(*) FROM asset_declarations')->fetchColumn();
$submitted = (int)$pdo->query("SELECT COUNT(*) FROM asset_declarations WHERE status='submitted'")->fetchColumn();
$drafts = $totalDecl - $submitted;
$totalPols = (int)$pdo->query('SELECT COUNT(*) FROM politicians')->fetchColumn();

$byYear = $pdo->query(
    "SELECT year, COUNT(*) AS cnt,
            SUM(total_deposits) AS deposits,
            SUM(total_debts) AS debts
       FROM asset_declarations
      WHERE status = 'submitted'
   GROUP BY year
   ORDER BY year DESC"
)->fetchAll();

$byParty = $pdo->query(
    "SELECT pa.name AS party_name,
            COUNT(ad.id) AS cnt,
            SUM(ad.total_deposits) AS deposits
       FROM asset_declarations ad
       JOIN politicians p ON p.id = ad.politician_id
  LEFT JOIN parties pa ON pa.id = p.party_id
      WHERE ad.status = 'submitted'
   GROUP BY pa.name
   ORDER BY cnt DESC"
)->fetchAll();

$latestYear = (int)($pdo->query('SELECT MAX(year) FROM asset_declarations')->fetchColumn() ?? date('Y'));

$notSubmittedStmt = $pdo->prepare(
    "SELECT u.first_name, u.last_name,
            pa.name AS party_name,
            po.title AS position_title
       FROM politicians p
       JOIN users u ON u.id = p.user_id
  LEFT JOIN parties pa ON pa.id = p.party_id
  LEFT JOIN positions po ON po.id = p.position_id
      WHERE p.id NOT IN (
          SELECT politician_id FROM asset_declarations
           WHERE year = :yr AND status = 'submitted'
      )"
);
$notSubmittedStmt->execute([':yr' => $latestYear]);
$notSubmitted = $notSubmittedStmt->fetchAll();

$topDeposits = $pdo->query(
    "SELECT u.first_name, u.last_name,
            pa.name AS party_name,
            ad.year, ad.total_deposits
       FROM asset_declarations ad
       JOIN politicians p ON p.id = ad.politician_id
       JOIN users u ON u.id = p.user_id
  LEFT JOIN parties pa ON pa.id = p.party_id
      WHERE ad.status = 'submitted'
   ORDER BY ad.total_deposits DESC
      LIMIT 10"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports &ndash; Admin</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link rel="stylesheet" href="style.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Reports &amp; Statistics</h1>
      <p class="subtitle">Asset Declaration Analytics Dashboard</p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../../index.php" class="nav-link">Home</a>
    <a href="../dashboard/dashboard.php" class="nav-link">Dashboard</a>
    <a href="admin_dashboard.php" class="nav-link">Admin</a>
    <a href="manage_users.php" class="nav-link">Manage Users</a>
    <a href="manage_submissions.php" class="nav-link">Submissions</a>
    <a href="configure_system.php" class="nav-link">Configure</a>
    <a href="reports.php" class="nav-link active">Reports</a>
    <a href="../search/search_dashboard.php" class="nav-link">Search</a>
    <a href="../search/statistics.php" class="nav-link">Statistics</a>
    <a href="../../auth/logout.php" class="nav-link nav-logout">Logout</a>
  </nav>
  <main>
    <section id="summary-section">
      <h2>Summary</h2>
      <div class="panel-grid">
        <div class="panel"><h2>Total Declarations</h2><p class="stat-number"><?= $totalDecl ?></p><p class="stat-label">all time</p></div>
        <div class="panel"><h2>Submitted</h2><p class="stat-number"><?= $submitted ?></p><p class="stat-label">officially submitted</p></div>
        <div class="panel"><h2>Drafts</h2><p class="stat-number"><?= $drafts ?></p><p class="stat-label">pending submission</p></div>
        <div class="panel"><h2>Politicians</h2><p class="stat-number"><?= $totalPols ?></p><p class="stat-label">registered in system</p></div>
      </div>
    </section>

    <section id="charts-section">
      <h2>Visual Analytics</h2>
      <div class="panel-grid" style="grid-template-columns:1fr 1fr;">
        <div class="panel" style="padding:20px;"><h2>Submissions per Year</h2><canvas id="chartYear" height="200"></canvas></div>
        <div class="panel" style="padding:20px;"><h2>Submissions per Party</h2><canvas id="chartParty" height="200"></canvas></div>
      </div>
    </section>

    <section id="year-section">
      <h2>By Year</h2>
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Year</th><th>Submissions</th><th>Total Deposits</th><th>Total Debts</th></tr></thead>
          <tbody>
            <?php foreach ($byYear as $r): ?>
            <tr>
              <td><?= (int)$r['year'] ?></td>
              <td><?= (int)$r['cnt'] ?></td>
              <td>&euro;<?= number_format((float)$r['deposits'], 2) ?></td>
              <td>&euro;<?= number_format((float)$r['debts'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section id="party-section">
      <h2>By Party</h2>
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Party</th><th>Submissions</th><th>Total Deposits</th></tr></thead>
          <tbody>
            <?php foreach ($byParty as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['party_name'] ?? 'Unknown') ?></td>
              <td><?= (int)$r['cnt'] ?></td>
              <td>&euro;<?= number_format((float)$r['deposits'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section id="not-submitted-section">
      <h2>Not Submitted for <?= $latestYear ?></h2>
      <?php if (empty($notSubmitted)): ?>
        <div class="alert alert-success">All politicians have submitted for <?= $latestYear ?>! ✓</div>
      <?php else: ?>
        <div class="table-wrapper">
          <table class="data-table">
            <thead><tr><th>Politician</th><th>Party</th><th>Position</th></tr></thead>
            <tbody>
              <?php foreach ($notSubmitted as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                <td><?= htmlspecialchars($r['party_name'] ?? '–') ?></td>
                <td><?= htmlspecialchars($r['position_title'] ?? '–') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <section id="top-deposits-section">
      <h2>Top 10 Deposits</h2>
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Politician</th><th>Party</th><th>Year</th><th>Total Deposits</th></tr></thead>
          <tbody>
            <?php foreach ($topDeposits as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
              <td><?= htmlspecialchars($r['party_name'] ?? '–') ?></td>
              <td><?= (int)$r['year'] ?></td>
              <td>&euro;<?= number_format((float)$r['total_deposits'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
  <footer id="page-footer"><p>CEI326 Web Engineering 2026 &mdash; Admin Module</p></footer>

  <script>
    // Bar chart: submission counts grouped by year.
    var yearLabels = <?= json_encode(array_map('intval', array_column($byYear, 'year'))) ?>;
    var yearData = <?= json_encode(array_map('intval', array_column($byYear, 'cnt'))) ?>;
    new Chart(document.getElementById('chartYear'), {
      type: 'bar',
      data: { labels: yearLabels, datasets: [{ label: 'Submissions', data: yearData, backgroundColor: '#1a6fa8' }] },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    // Pie chart: submission counts grouped by party.
    var partyLabels = <?= json_encode(array_map(fn($r) => $r['party_name'] ?? 'Unknown', $byParty)) ?>;
    var partyData = <?= json_encode(array_map('intval', array_column($byParty, 'cnt'))) ?>;
    var partyColors = ['#1a6fa8','#27ae60','#e67e22','#8e44ad','#e74c3c','#1abc9c'];
    new Chart(document.getElementById('chartParty'), {
      type: 'pie',
      data: { labels: partyLabels, datasets: [{ data: partyData, backgroundColor: partyColors }] },
      options: { responsive: true }
    });
  </script>
</body>
</html>