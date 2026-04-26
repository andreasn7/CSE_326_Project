<?php
session_start();
require_once '../../includes/db.php';
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && $_SESSION['role'] === 'admin';

$keyword = trim($_GET['keyworda'] ?? '');
$partyId = (int)($_GET['party'] ?? 0);
$posId = (int)($_GET['position'] ?? 0);
$searched = $keyword !== '' || $partyId || $posId;
$results = [];

if ($searched) {
    $sql = 'SELECT u.first_name, u.last_name,
                   pa.name AS party_name, pa.short_name AS party_short,
                   po.title AS position_title,
                   d.name AS district_name,
                   p.id AS politician_id,
                   (SELECT COUNT(*) FROM asset_declarations ad
                     WHERE ad.politician_id = p.id AND ad.status = "submitted") AS decl_count
              FROM politicians p
              JOIN users u ON u.id = p.user_id
         LEFT JOIN parties pa ON pa.id = p.party_id
         LEFT JOIN positions po ON po.id = p.position_id
         LEFT JOIN districts d ON d.id = p.district_id
             WHERE 1=1';
    $params = [];

    if ($keyword !== '') {
        $sql .= ' AND (u.first_name LIKE :kw1
                   OR u.last_name LIKE :kw2
                   OR pa.name LIKE :kw3
                   OR CONCAT(u.first_name, " ", u.last_name) LIKE :kw4)';
        $params[':kw1'] = '%' . $keyword . '%';
        $params[':kw2'] = '%' . $keyword . '%';
        $params[':kw3'] = '%' . $keyword . '%';
        $params[':kw4'] = '%' . $keyword . '%';
    }
    if ($partyId) { $sql .= ' AND p.party_id = :pid'; $params[':pid'] = $partyId; }
    if ($posId) { $sql .= ' AND p.position_id = :poid'; $params[':poid'] = $posId; }

    $sql .= ' ORDER BY u.last_name, u.first_name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Pre-load every politician's declarations and committees in two
    // batched queries to keep the result table to two round trips
    // regardless of result count.
    if (!empty($results)) {
        $polIds = array_map(static fn($r) => (int)$r['politician_id'], $results);
        $placeholders = implode(',', array_fill(0, count($polIds), '?'));

        $declStmt = $pdo->prepare(
            "SELECT politician_id, year, total_deposits, total_debts
               FROM asset_declarations
              WHERE status = 'submitted'
                AND politician_id IN ($placeholders)
           ORDER BY year DESC"
        );
        $declStmt->execute($polIds);
        $declsByPol = [];
        foreach ($declStmt->fetchAll() as $row) {
            $declsByPol[(int)$row['politician_id']][] = $row;
        }

        $commStmt = $pdo->prepare(
            "SELECT pc.politician_id, c.name
               FROM politician_committees pc
               JOIN committees c ON c.id = pc.committee_id
              WHERE pc.politician_id IN ($placeholders)
           ORDER BY c.name"
        );
        $commStmt->execute($polIds);
        $commsByPol = [];
        foreach ($commStmt->fetchAll() as $row) {
            $commsByPol[(int)$row['politician_id']][] = $row['name'];
        }
    } else {
        $declsByPol = [];
        $commsByPol = [];
    }
}

$parties = $pdo->query('SELECT * FROM parties ORDER BY name')->fetchAll();
$positions = $pdo->query('SELECT * FROM positions ORDER BY title')->fetchAll();

$totalDecl = (int) $pdo->query("SELECT COUNT(*) FROM asset_declarations WHERE status='submitted'")->fetchColumn();
$totalDeposits = (float)$pdo->query("SELECT SUM(total_deposits) FROM asset_declarations WHERE status='submitted'")->fetchColumn();
$totalDebts = (float)$pdo->query("SELECT SUM(total_debts) FROM asset_declarations WHERE status='submitted'")->fetchColumn();
$totalPols = (int) $pdo->query('SELECT COUNT(*) FROM politicians')->fetchColumn();


function formatAmount(float $amount): string {
    if ($amount >= 1_000_000) {
        return '€' . number_format($amount / 1_000_000, 2) . 'M';
    }
    return '€' . number_format($amount / 1_000, 1) . 'K';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search &ndash; Registry of Assets</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Search Records</h1>
      <p class="subtitle">Public Asset Declaration Search</p>
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
    <a href="search_dashboard.php" class="nav-link active">Search</a>
    <a href="statistics.php" class="nav-link">Statistics</a>
    <?php if ($isLoggedIn): ?>
      <a href="../../auth/logout.php" class="nav-link nav-logout">Logout</a>
    <?php endif; ?>
  </nav>
  <main>
    <section id="search-section">
      <h2>Search</h2>
      <form method="GET" action="search_dashboard.php#search-section" id="search-form">
        <input type="hidden" name="party" id="hidden-party" value="<?= (int)$partyId ?>">
        <input type="hidden" name="position" id="hidden-position" value="<?= (int)$posId ?>">
        <div class="search-box">
          <input type="text" name="keyword" class="search-input"
                 placeholder="Search by politician name or party..."
                 value="<?= htmlspecialchars($keyword) ?>">
          <button type="submit" class="btn btn-search">Search</button>
        </div>
        <div class="filter-bar">
          <span class="filter-label">Party:</span>
          <button type="button" class="filter-btn <?= !$partyId ? 'active' : '' ?>" data-filter="party" data-value="0">All</button>
          <?php foreach ($parties as $p): ?>
            <button type="button" class="filter-btn <?= $partyId == $p['id'] ? 'active' : '' ?>"
                    data-filter="party" data-value="<?= (int)$p['id'] ?>">
              <?= htmlspecialchars($p['short_name'] ?: $p['name']) ?>
            </button>
          <?php endforeach; ?>
        </div>
        <div class="filter-bar" style="margin-top:8px;">
          <span class="filter-label">Position:</span>
          <button type="button" class="filter-btn <?= !$posId ? 'active' : '' ?>" data-filter="position" data-value="0">All</button>
          <?php foreach ($positions as $pos): ?>
            <button type="button" class="filter-btn <?= $posId == $pos['id'] ? 'active' : '' ?>"
                    data-filter="position" data-value="<?= (int)$pos['id'] ?>">
              <?= htmlspecialchars($pos['title']) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </form>
    </section>

    <?php if (!$searched): ?>
    <section id="quick-stats-section">
      <h2>System Overview</h2>
      <div class="stats-overview-grid">
        <div class="stats-card"><span class="stats-icon">📋</span><p class="stats-number"><?= $totalDecl ?></p><p class="stats-label">Total Declarations</p></div>
        <div class="stats-card"><span class="stats-icon">👥</span><p class="stats-number"><?= $totalPols ?></p><p class="stats-label">Politicians</p></div>
        <div class="stats-card"><span class="stats-icon">💰</span><p class="stats-number"><?= formatAmount($totalDeposits) ?></p><p class="stats-label">Total Deposits Declared</p></div>
        <div class="stats-card"><span class="stats-icon">📉</span><p class="stats-number"><?= formatAmount($totalDebts) ?></p><p class="stats-label">Total Debts Declared</p></div>
      </div>
      <p style="margin-top:12px;font-size:0.9rem;color:#888;">Use the search form above to find specific politicians. Visit <a href="statistics.php" style="color:#8e44ad;">Statistics</a> for detailed analytics.</p>
    </section>
    <?php endif; ?>

    <?php if ($searched): ?>
    <section id="results-section">
      <h2>Results <small class="result-count">&ndash; <?= count($results) ?> found</small></h2>
      <?php if (empty($results)): ?>
        <p>No records match your search criteria.</p>
      <?php else: ?>
        <div class="results-list">
          <?php foreach ($results as $r):
                  $polId = (int)$r['politician_id'];
                  $declRows = $declsByPol[$polId] ?? [];
                  $committees = $commsByPol[$polId] ?? [];
          ?>
          <div class="result-card">
            <div class="result-card-header">
              <h2><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></h2>
              <span class="badge"><?= htmlspecialchars($r['party_name'] ?? 'No Party') ?></span>
            </div>
            <div class="result-meta" style="margin-bottom:10px;">
              <span>🏛️ <?= htmlspecialchars($r['position_title'] ?? 'Unknown position') ?></span>
              <span>📍 <?= htmlspecialchars($r['district_name'] ?? '–') ?></span>
              <span>📋 <?= (int)$r['decl_count'] ?> declaration(s)</span>
            </div>

            <?php if (!empty($committees)): ?>
              <div style="margin-bottom:10px;display:flex;gap:6px;flex-wrap:wrap;">
                <?php foreach ($committees as $cName): ?>
                  <span class="badge" style="background:#e8edf3;color:#1a3a5c;font-weight:500;">🪧 <?= htmlspecialchars($cName) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($declRows)): ?>
              <div style="font-size:0.88rem;color:#555;">
                <?php foreach ($declRows as $dr): ?>
                  <span style="display:inline-block;margin-right:12px;">
                    <strong><?= (int)$dr['year'] ?>:</strong>
                    Deposits &euro;<?= number_format((float)$dr['total_deposits'], 0) ?>,
                    Debts &euro;<?= number_format((float)$dr['total_debts'], 0) ?>
                  </span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if (!$isLoggedIn): ?>
    <section id="register-section">
      <h2>Register</h2>
      <div class="tips-panel">
        <p>Create an account to submit your own asset declaration. <a href="../../auth/register.php" style="color:#8e44ad;font-weight:700;">Register here →</a></p>
      </div>
    </section>
    <?php endif; ?>

    <section id="tips-section">
      <h2>Search Tips</h2>
      <div class="tips-panel">
        <p>Search by politician name or party name. Use the party and position filters to narrow your results. Visit <a href="statistics.php" style="color:#8e44ad;font-weight:700;">Statistics</a> for grouped analytics by year and party.</p>
      </div>
    </section>
  </main>
  <footer id="page-footer"><p>CEI326 Web Engineering 2026 &mdash; Search Module</p></footer>

  <script>
    // Apply a filter button click by writing the chosen id into the
    // matching hidden input and resubmitting the form.
    var form = document.getElementById('search-form');
    document.querySelectorAll('.filter-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = document.getElementById('hidden-' + btn.dataset.filter);
        if (target) target.value = btn.dataset.value;
        var actionBase = form.action.split('#')[0];
        form.action = actionBase + '#search-section';
        form.submit();
      });
    });
  </script>
</body>
</html>