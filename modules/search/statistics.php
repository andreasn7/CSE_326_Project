<?php
session_start();
require_once '../../includes/db.php';
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && $_SESSION['role'] === 'admin';

$byYear = $pdo->query(
    "SELECT year, COUNT(*) AS total,
            SUM(total_deposits) AS deposits,
            SUM(total_debts) AS debts,
            SUM(annual_income) AS income,
            SUM(shares_value) AS shares,
            SUM(vehicles_count) AS vehicles
       FROM asset_declarations
      WHERE status = 'submitted'
   GROUP BY year
   ORDER BY year DESC"
)->fetchAll();

$byParty = $pdo->query(
    "SELECT pa.name AS party_name, pa.short_name,
            COUNT(ad.id) AS total,
            SUM(ad.total_deposits) AS deposits,
            SUM(ad.total_debts) AS debts,
            SUM(ad.annual_income) AS income
       FROM asset_declarations ad
       JOIN politicians p ON p.id = ad.politician_id
  LEFT JOIN parties pa ON pa.id = p.party_id
      WHERE ad.status = 'submitted'
   GROUP BY pa.name, pa.short_name
   ORDER BY deposits DESC"
)->fetchAll();

// Per-politician aggregates. The LEFT JOIN on declarations ensures
// politicians with zero submissions still appear in the table.
$polStats = $pdo->query(
    "SELECT u.first_name, u.last_name,
            pa.name AS party_name, d.name AS district_name, p.children,
            COUNT(ad.id) AS decl_count,
            SUM(ad.total_deposits) AS deposits,
            SUM(ad.total_debts) AS debts,
            SUM(ad.annual_income) AS income,
            SUM(ad.vehicles_count) AS vehicles
       FROM politicians p
       JOIN users u ON u.id = p.user_id
  LEFT JOIN parties pa ON pa.id = p.party_id
  LEFT JOIN districts d ON d.id = p.district_id
  LEFT JOIN asset_declarations ad ON ad.politician_id = p.id AND ad.status = 'submitted'
   GROUP BY p.id, u.first_name, u.last_name, pa.name, d.name, p.children
   ORDER BY deposits DESC"
)->fetchAll();

$byDistrict = $pdo->query(
    "SELECT d.name AS district_name, COUNT(p.id) AS total
       FROM politicians p
  LEFT JOIN districts d ON d.id = p.district_id
   GROUP BY d.name
   ORDER BY total DESC"
)->fetchAll();

$childrenStats = $pdo->query(
    'SELECT children, COUNT(*) AS cnt
       FROM politicians
   GROUP BY children
   ORDER BY children'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statistics &ndash; Registry of Assets</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link rel="stylesheet" href="style.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Statistics</h1>
      <p class="subtitle">Asset Declaration Analytics &ndash; Public View</p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../../index.php" class="nav-link">Home</a>
    <?php if ($isLoggedIn): ?>
      <a href="../dashboard/dashboard.php" class="nav-link">Dashboard</a>
      <?php if ($isAdmin): ?><a href="../admin/admin_dashboard.php" class="nav-link">Admin</a><?php endif; ?>
      <a href="../submit/submit_dashboard.php" class="nav-link">Submit</a>
    <?php else: ?>
      <a href="../../auth/login.php" class="nav-link">Login</a>
      <a href="../../auth/register.php" class="nav-link">Register</a>
    <?php endif; ?>
    <a href="search_dashboard.php" class="nav-link">Search</a>
    <a href="statistics.php" class="nav-link active">Statistics</a>
    <?php if ($isLoggedIn): ?><a href="../../auth/logout.php" class="nav-link nav-logout">Logout</a><?php endif; ?>
  </nav>
  <main>
    <section id="charts-section">
      <h2>Visual Analytics</h2>
      <div class="stats-charts-grid">
        <div class="chart-card"><h3 class="chart-title">Deposits by Year</h3><canvas id="chartDepositsYear" height="200"></canvas></div>
        <div class="chart-card"><h3 class="chart-title">Deposits by Party</h3><canvas id="chartDepositsParty" height="200"></canvas></div>
        <div class="chart-card"><h3 class="chart-title">Politicians by District</h3><canvas id="chartDistrict" height="200"></canvas></div>
        <div class="chart-card"><h3 class="chart-title">Debts vs Deposits (by Year)</h3><canvas id="chartDebtsDeposits" height="200"></canvas></div>
      </div>
    </section>

    <section id="year-section">
      <h2>By Year</h2>
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Year</th><th>Submissions</th><th>Total Deposits</th><th>Total Debts</th><th>Total Income</th><th>Total Shares</th><th>Total Vehicles</th></tr></thead>
          <tbody>
            <?php foreach ($byYear as $r): ?>
            <tr>
              <td><strong><?= (int)$r['year'] ?></strong></td>
              <td><?= (int)$r['total'] ?></td>
              <td>&euro;<?= number_format((float)$r['deposits'], 2) ?></td>
              <td>&euro;<?= number_format((float)$r['debts'], 2) ?></td>
              <td>&euro;<?= number_format((float)$r['income'], 2) ?></td>
              <td>&euro;<?= number_format((float)$r['shares'], 2) ?></td>
              <td><?= (int)$r['vehicles'] ?></td>
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
          <thead><tr><th>Party</th><th>Short</th><th>Submissions</th><th>Total Deposits</th><th>Total Debts</th><th>Total Income</th></tr></thead>
          <tbody>
            <?php foreach ($byParty as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['party_name'] ?? 'Unknown') ?></td>
              <td><?= htmlspecialchars($r['short_name'] ?? '–') ?></td>
              <td><?= (int)$r['total'] ?></td>
              <td>&euro;<?= number_format((float)$r['deposits'], 2) ?></td>
              <td>&euro;<?= number_format((float)$r['debts'], 2) ?></td>
              <td>&euro;<?= number_format((float)$r['income'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section id="politician-section">
      <h2>Per Politician</h2>
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Politician</th><th>Party</th><th>District</th><th>Children</th><th>Declarations</th><th>Total Deposits</th><th>Total Debts</th><th>Total Income</th><th>Vehicles</th></tr></thead>
          <tbody>
            <?php foreach ($polStats as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
              <td><?= htmlspecialchars($r['party_name'] ?? '–') ?></td>
              <td><?= htmlspecialchars($r['district_name'] ?? '–') ?></td>
              <td><?= (int)$r['children'] ?></td>
              <td><?= (int)$r['decl_count'] ?></td>
              <td>&euro;<?= number_format((float)($r['deposits'] ?? 0), 2) ?></td>
              <td>&euro;<?= number_format((float)($r['debts'] ?? 0), 2) ?></td>
              <td>&euro;<?= number_format((float)($r['income'] ?? 0), 2) ?></td>
              <td><?= (int)($r['vehicles'] ?? 0) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section id="demo-section">
      <h2>Demographics</h2>
      <div class="stats-charts-grid">
        <div>
          <h3 class="chart-title demo-title">By District</h3>
          <div class="table-wrapper">
            <table class="data-table">
              <thead><tr><th>District</th><th>Politicians</th></tr></thead>
              <tbody>
                <?php foreach ($byDistrict as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['district_name'] ?? 'Unknown') ?></td>
                  <td><?= (int)$r['total'] ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div>
          <h3 class="chart-title demo-title">Children Distribution</h3>
          <div class="table-wrapper">
            <table class="data-table">
              <thead><tr><th>Children</th><th>Politicians</th></tr></thead>
              <tbody>
                <?php foreach ($childrenStats as $r): ?>
                <tr>
                  <td><?= (int)$r['children'] ?></td>
                  <td><?= (int)$r['cnt'] ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
  </main>
  <footer id="page-footer"><p>CEI326 Web Engineering 2026 &mdash; Search Module</p></footer>

  <script>
    var colors = ['#8e44ad','#27ae60','#e67e22','#1a6fa8','#e74c3c','#1abc9c'];
    var yearLabels = <?= json_encode(array_map('intval', array_column($byYear, 'year'))) ?>;
    var yearDeposits = <?= json_encode(array_map(static fn($r) => round((float)$r['deposits'], 2), $byYear)) ?>;
    var yearDebts = <?= json_encode(array_map(static fn($r) => round((float)$r['debts'], 2), $byYear)) ?>;

    new Chart(document.getElementById('chartDepositsYear'), {
      type: 'bar',
      data: { labels: yearLabels, datasets: [{ label: 'Deposits', data: yearDeposits, backgroundColor: '#8e44ad' }] },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    var partyLabels = <?= json_encode(array_map(static fn($r) => $r['short_name'] ?: ($r['party_name'] ?? '?'), $byParty)) ?>;
    var partyDeposits = <?= json_encode(array_map(static fn($r) => round((float)$r['deposits'], 2), $byParty)) ?>;
    new Chart(document.getElementById('chartDepositsParty'), {
      type: 'pie',
      data: { labels: partyLabels, datasets: [{ data: partyDeposits, backgroundColor: colors }] },
      options: { responsive: true }
    });

    var distLabels = <?= json_encode(array_map(static fn($r) => $r['district_name'] ?? 'Unknown', $byDistrict)) ?>;
    var distData = <?= json_encode(array_map(static fn($r) => (int)$r['total'], $byDistrict)) ?>;
    new Chart(document.getElementById('chartDistrict'), {
      type: 'doughnut',
      data: { labels: distLabels, datasets: [{ data: distData, backgroundColor: colors }] },
      options: { responsive: true }
    });

    new Chart(document.getElementById('chartDebtsDeposits'), {
      type: 'bar',
      data: {
        labels: yearLabels,
        datasets: [
          { label: 'Deposits', data: yearDeposits, backgroundColor: '#8e44ad' },
          { label: 'Debts', data: yearDebts, backgroundColor: '#e74c3c' }
        ]
      },
      options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });
  </script>
</body>
</html>