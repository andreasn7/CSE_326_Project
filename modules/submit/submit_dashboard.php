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

$errors  = [];
$success = false;
$old     = [];

// Form handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['entry-title'] ?? '');
    $category    = trim($_POST['entry-category'] ?? '');
    $description = trim($_POST['entry-description'] ?? '');

    $old = compact('title', 'category', 'description');

    $allowed_categories = ['report', 'project', 'other'];

    if ($title === '') {
        $errors[] = 'Entry title is required.';
    }
    if (!in_array($category, $allowed_categories, true)) {
        $errors[] = 'Please select a valid category.';
    }
    if ($description === '') {
        $errors[] = 'Description is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO posts (user_id, title, category, description) VALUES (:uid, :t, :c, :d)'
        );
        $stmt->execute([
            ':uid' => $_SESSION['user_id'],
            ':t'   => $title,
            ':c'   => $category,
            ':d'   => $description,
        ]);
        $success = true;
        $old     = [];
    }
}

// Load recent submissions
$recent = $pdo->query(
    'SELECT p.title, p.category, u.username, p.created_at
     FROM posts p JOIN users u ON u.id = p.user_id
     ORDER BY p.created_at DESC LIMIT 5'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Submit Entry</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Submit Entry</h1>
      <p class="subtitle">Add a New Record to the System</p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../../index.php" class="nav-link">Home</a>
    <a href="../dashboard/dashboard.php" class="nav-link">Dashboard</a>
    <?php if ($isAdmin): ?>
    <a href="../admin/admin_dashboard.php" class="nav-link">Admin</a>
    <?php endif; ?>
    <a href="submit_dashboard.php" class="nav-link active">Submit</a>
    <a href="../search/search_dashboard.php" class="nav-link">Search</a>
    <div class="nav-spacer"></div>
    <a href="../../auth/logout.php" class="nav-link nav-logout">Logout</a>
  </nav>
  <main>
    <section id="form-section">
      <h2>New Submission Form</h2>
      <div class="form-card">

        <?php if ($success): ?>
          <div class="alert alert-success" id="form-alert">
            Entry submitted successfully!
          </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-error" id="form-alert">
            <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
          </div>
        <?php endif; ?>

        <form method="POST" action="submit_dashboard.php" id="submission-form">
          <div class="form-group">
            <label for="entry-title">Entry Title</label>
            <input type="text" id="entry-title" name="entry-title" class="form-control"
                   value="<?= htmlspecialchars($old['title'] ?? '') ?>"
                   placeholder="Enter a title..." required>
          </div>
          <div class="form-group">
            <label for="entry-category">Category</label>
            <select id="entry-category" name="entry-category" class="form-control" required>
              <option value="">-- Select a category --</option>
              <option value="report" <?= ($old['category'] ?? '') === 'report' ? 'selected' : '' ?>>Report</option>
              <option value="project" <?= ($old['category'] ?? '') === 'project' ? 'selected' : '' ?>>Project</option>
              <option value="other" <?= ($old['category'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label for="entry-description">Description</label>
            <textarea id="entry-description" name="entry-description" class="form-control"
                      rows="4" placeholder="Describe the entry..."><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Submit Entry</button>
            <button type="button" class="btn btn-secondary" id="clear-form-btn">Clear Form</button>
          </div>
        </form>
      </div>
    </section>
    <section id="guidelines-section">
      <h2>Submission Guidelines</h2>
      <div class="info-panel">
        <h2>Before You Submit</h2>
        <p>Please complete all required fields before submitting the form.</p>
      </div>
    </section>
    <section id="recent-section">
      <h2>Recent Submissions</h2>
      <?php if (empty($recent)): ?>
        <p>No submissions yet.</p>
      <?php else: ?>
        <div class="card-list">
          <?php foreach ($recent as $r): ?>
            <div class="card">
              <div class="card-body">
                <h2><?= htmlspecialchars($r['title']) ?></h2>
                <p>By <?= htmlspecialchars($r['username']) ?> ยท <?= htmlspecialchars(date('d M Y', strtotime($r['created_at']))) ?></p>
              </div>
              <span class="tag"><?= htmlspecialchars(ucfirst($r['category'])) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
  <footer id="page-footer">
    <p>CEI326 Web Engineering 2026 - Submit Module</p>
  </footer>

  <script>
    const submissionForm = document.getElementById('submission-form');
    const clearFormButton = document.getElementById('clear-form-btn');
    const formAlert = document.getElementById('form-alert');

    clearFormButton.addEventListener('click', function () {
      submissionForm.querySelector('#entry-title').value = '';
      submissionForm.querySelector('#entry-category').value = '';
      submissionForm.querySelector('#entry-description').value = '';

      if (formAlert) {
        formAlert.style.display = 'none';
      }
    });
  </script>
</body>
</html>
