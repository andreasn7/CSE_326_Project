<?php
// Session
session_start();

// State
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Web Engineering Project</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Registry of Assets</h1>
      <p class="subtitle">System for Submitting Asset Declarations</p>    </div>
  </header>
  <nav id="main-nav">
    <a href="index.php" class="nav-link active">Home</a>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="modules/dashboard/dashboard.php" class="nav-link">Dashboard</a>
        <?php if ($isAdmin): ?>
        <a href="modules/admin/admin_dashboard.php" class="nav-link">Admin</a>
        <?php endif; ?>
        <a href="modules/submit/submit_dashboard.php" class="nav-link">Submit</a>
        <a href="modules/search/search_dashboard.php" class="nav-link">Search</a>
        <a href="auth/logout.php" class="nav-link nav-logout">Logout</a>
    <?php else: ?>
        <a href="auth/login.php" class="nav-link">Login</a>
        <a href="auth/register.php" class="nav-link">Register</a>
    <?php endif; ?>
</nav>
  <main>
    <section id="hero-section">
      <h2>Welcome to the Official Registry of Assets</h2>
      <p>Use the navigation above to access the available system modules.</p>
    </section>
    <section id="modules-section">
      <h2>System Modules</h2>
      <div class="card-grid">
        <div class="card">
          <h2>Dashboard</h2>
          <p>View your personal activity, recent posts, and quick actions.</p>
          <a href="modules/dashboard/dashboard.php" class="btn">Open Dashboard</a>
        </div>
        <div class="card">
          <h2>Submit Module</h2>
          <p>Use the submission form to add a new record to the system.</p>
          <a href="modules/submit/submit_dashboard.php" class="btn">Open Submit Module</a>
        </div>
        <div class="card">
          <h2>Search Module</h2>
          <p>Search existing records using the available search tools.</p>
          <a href="modules/search/search_dashboard.php" class="btn">Open Search Module</a>
        </div>
      </div>
    </section>
    <section id="about-section">
      <h2>About This Project</h2>
      <p>This project was developed for the CEI326 Web Engineering course.</p>
    </section>
    <section id="highlights-section">
      <h2>Main Modules</h2>
      <article class="project-article">
        <h3>Three Main Modules</h3>
        <p>The system is organised into three main modules: Dashboard, Submit, and Search. Administrators also have access to a dedicated Admin Panel via the Dashboard sidebar.</p>
      </article>
    </section>
  </main>
  <footer id="main-footer">
    <p>CEI326 Web Engineering - Registry of Assets Project</p>
  </footer>
</body>
</html>
