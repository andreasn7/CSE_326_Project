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

// Filters
$keyword = trim($_GET['keyword'] ?? '');

// Load posts
if ($keyword !== '') {
    $stmt = $pdo->prepare(
        'SELECT p.*, u.username
         FROM posts p
         JOIN users u ON u.id = p.user_id
         WHERE p.title LIKE :kw1 OR p.description LIKE :kw2 OR u.username LIKE :kw3
         ORDER BY p.created_at DESC'
    );
    $stmt->execute([
        ':kw1' => '%' . $keyword . '%',
        ':kw2' => '%' . $keyword . '%',
        ':kw3' => '%' . $keyword . '%',
    ]);
} else {
    $stmt = $pdo->prepare(
        'SELECT p.*, u.username
         FROM posts p
         JOIN users u ON u.id = p.user_id
         ORDER BY p.created_at DESC'
    );
    $stmt->execute();
}

$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Posts – CEI326</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <?php include $_SERVER['DOCUMENT_ROOT'] . '/dashboard/CSE_326_Project_NEW/includes/header.php'; ?>
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>All Posts</h1>
      <p class="subtitle">Browse and search all submitted entries</p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../../index.php" class="nav-link">Home</a>
    <a href="../dashboard/dashboard.php" class="nav-link">Dashboard</a>
    <a href="../submit/submit_dashboard.php" class="nav-link">Submit</a>
    <a href="../search/search_dashboard.php" class="nav-link">Search</a>
    <a href="../search/statistics.php" class="nav-link">Statistics</a>
    <a href="list.php" class="nav-link active">Posts</a>
    <a href="../../auth/logout.php" class="nav-link nav-logout">Logout</a>
  </nav>
  <main>
    <section id="results-section">
      <h2>
        All Posts
        <?php if ($keyword !== ''): ?>
          <small class="result-count">
            – <?= count($posts) ?> result(s) for "<?= htmlspecialchars($keyword) ?>"
          </small>
        <?php endif; ?>
      </h2>

      <?php if (empty($posts)): ?>
        <p>No posts found<?= $keyword !== '' ? ' matching your search.' : '.' ?></p>
      <?php else: ?>
        <div class="results-list">
          <?php foreach ($posts as $post): ?>
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
  </main>
  <footer id="page-footer">
    <p>CEI326 Web Engineering 2026 – Posts</p>
  </footer>
</body>
</html>
