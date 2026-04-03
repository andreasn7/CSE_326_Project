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

// State
$isAdmin = $_SESSION['role'] === 'admin';

$keyword = trim($_GET['keyword'] ?? '');
$filter  = $_GET['filter'] ?? 'all';
$allowed_filters = ['all', 'report', 'project', 'other'];
if (!in_array($filter, $allowed_filters, true)) {
    $filter = 'all';
}

// Load search results
if ($keyword !== '' && $filter !== 'all') {
    $stmt = $pdo->prepare(
        'SELECT p.*, u.username FROM posts p JOIN users u ON u.id = p.user_id
        WHERE (p.title LIKE :kw1 OR p.description LIKE :kw2 OR u.username LIKE :kw3)
          AND p.category = :cat
        ORDER BY p.created_at DESC'
    );
    $stmt->execute([
        ':kw1' => '%' . $keyword . '%',
        ':kw2' => '%' . $keyword . '%',
        ':kw3' => '%' . $keyword . '%',
        ':cat' => $filter,
    ]);
} elseif ($keyword !== '') {
    $stmt = $pdo->prepare(
        'SELECT p.*, u.username FROM posts p JOIN users u ON u.id = p.user_id
         WHERE p.title LIKE :kw1 OR p.description LIKE :kw2 OR u.username LIKE :kw3
         ORDER BY p.created_at DESC'
    );
    $stmt->execute([
        ':kw1' => '%' . $keyword . '%',
        ':kw2' => '%' . $keyword . '%',
        ':kw3' => '%' . $keyword . '%',
    ]);
} elseif ($filter !== 'all') {
    $stmt = $pdo->prepare(
        'SELECT p.*, u.username FROM posts p JOIN users u ON u.id = p.user_id
         WHERE p.category = :cat ORDER BY p.created_at DESC'
    );
    $stmt->execute([':cat' => $filter]);
} else {
    $stmt = $pdo->query(
        'SELECT p.*, u.username FROM posts p JOIN users u ON u.id = p.user_id ORDER BY p.created_at DESC'
    );
}

$results  = $stmt->fetchAll();
$searched = $keyword !== '' || $filter !== 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search Records</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Search Records</h1>
      <p class="subtitle">Browse and Find Existing Entries</p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../../index.php" class="nav-link">Home</a>
    <a href="../dashboard/dashboard.php" class="nav-link">Dashboard</a>
    <?php if ($isAdmin): ?>
    <a href="../admin/admin_dashboard.php" class="nav-link">Admin</a>
    <?php endif; ?>
    <a href="../submit/submit_dashboard.php" class="nav-link">Submit</a>
    <a href="search_dashboard.php" class="nav-link active">Search</a>
    <div class="nav-spacer"></div>
    <a href="../../auth/logout.php" class="nav-link nav-logout">Logout</a>
  </nav>
  <main>
    <section id="search-section">
      <h2>Search</h2>
      <form method="GET" action="search_dashboard.php">
        <div class="search-box">
          <input type="text" name="keyword" class="search-input"
                 placeholder="Search by title, author, or description..."
                 value="<?= htmlspecialchars($keyword) ?>">
          <button type="submit" class="btn btn-search">Search</button>
        </div>
        <div class="filter-bar">
          <span class="filter-label">Filter by:</span>
          <?php foreach (['all' => 'All', 'report' => 'Reports', 'project' => 'Projects', 'other' => 'Other'] as $val => $label): ?>
            <button type="submit" name="filter" value="<?= $val ?>"
                    class="filter-btn <?= $filter === $val ? 'active' : '' ?>">
              <?= $label ?>
            </button>
          <?php endforeach; ?>
        </div>
      </form>
    </section>

    <section id="tips-section">
      <h2>Search Tips</h2>
      <div class="tips-panel">
        <p>Use keywords to search by title, author name, or description. Combine with a category filter to narrow down results.</p>
      </div>
    </section>

    <?php if ($searched): ?>
    <section id="results-section">
      <h2>Results
        <small class="result-count">
          – <?= count($results) ?> found
        </small>
      </h2>

      <?php if (empty($results)): ?>
        <p>No records match your search criteria.</p>
      <?php else: ?>
        <div class="results-list">
          <?php foreach ($results as $post): ?>
            <div class="result-card">
              <div class="result-card-header">
                <h2><?= htmlspecialchars($post['title']) ?></h2>
                <span class="badge"><?= htmlspecialchars(ucfirst($post['category'])) ?></span>
              </div>
              <p><?= htmlspecialchars($post['description'] ?? '–') ?></p>
              <div class="result-meta">
                <span>By: <?= htmlspecialchars($post['username']) ?></span>
                <span><?= htmlspecialchars(date('d M Y', strtotime($post['created_at']))) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
    <?php endif; ?>
  </main>
  <footer id="page-footer">
    <p>CEI326 Web Engineering 2026 - Search Module</p>
  </footer>
</body>
</html>
