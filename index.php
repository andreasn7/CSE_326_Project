<?php
session_start();
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Web Engineering Project</title>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Registry of Assets</h1>
      <p class="subtitle">System for Submitting Asset Declarations</p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="index.php" class="nav-link active">Home</a>
    <?php if ($isLoggedIn): ?>
      <a href="modules/dashboard/dashboard.php" class="nav-link">Dashboard</a>
      <?php if ($isAdmin): ?>
        <a href="modules/admin/admin_dashboard.php" class="nav-link">Admin</a>
      <?php endif; ?>
      <a href="modules/submit/submit_dashboard.php" class="nav-link">Submit</a>
      <a href="modules/search/search_dashboard.php" class="nav-link">Search</a>
      <a href="modules/search/statistics.php" class="nav-link">Statistics</a>
      <a href="auth/logout.php" class="nav-link nav-logout">Logout</a>
    <?php else: ?>
      <a href="auth/login.php" class="nav-link">Login</a>
      <a href="auth/register.php" class="nav-link">Register</a>
      <a href="modules/search/search_dashboard.php" class="nav-link">Search</a>
      <a href="modules/search/statistics.php" class="nav-link">Statistics</a>
    <?php endif; ?>
  </nav>
  <main>
    <section id="hero-section">
      <h2>Welcome to the Official Registry of Assets</h2>
      <p>Use the navigation above to access the available system modules. The Search and Statistics modules are publicly accessible &ndash; no login required.</p>
    </section>
    <section id="modules-section">
      <h2>System Modules</h2>
      <div class="card-grid">
        <div class="card">
          <h2>Search Module</h2>
          <p>Search politicians by name, party or position. Public access &ndash; no login required.</p>
          <a href="modules/search/search_dashboard.php" class="btn">Open Search</a>
        </div>
        <div class="card">
          <h2>Statistics</h2>
          <p>View grouped analytics by year, party and politician. Charts and detailed tables.</p>
          <a href="modules/search/statistics.php" class="btn">View Statistics</a>
        </div>
        <div class="card">
          <h2>Submit Module</h2>
          <p>Politicians can submit their asset declarations (Pothen Esches). Login required.</p>
          <a href="<?= $isLoggedIn ? 'modules/submit/submit_dashboard.php' : 'auth/login.php' ?>" class="btn">Submit Declaration</a>
        </div>
        <?php if ($isAdmin): ?>
        <div class="card">
          <h2>Admin Panel</h2>
          <p>Manage users, submissions, system configuration, and reports.</p>
          <a href="modules/admin/admin_dashboard.php" class="btn">Open Admin</a>
        </div>
        <?php else: ?>
        <div class="card">
          <h2>Dashboard</h2>
          <p>View your personal activity, recent declarations, and quick actions.</p>
          <a href="<?= $isLoggedIn ? 'modules/dashboard/dashboard.php' : 'auth/login.php' ?>" class="btn">Open Dashboard</a>
        </div>
        <?php endif; ?>
      </div>
    </section>
    <section id="about-section">
      <h2>About This System</h2>
      <p>This system enables the public to monitor the asset declarations (Pothen Esches) of officials of the Republic of Cyprus. Data is sourced from the official <a href="https://www.parliament.cy/el/ποθεν-εσχεσ" target="_blank" rel="noopener">Parliament of Cyprus</a> website.</p>
    </section>
    <section id="highlights-section">
      <h2>Main Modules</h2>
      <article class="project-article">
        <h3>Four Main Modules</h3>
        <p>The system is organised into four modules: <strong>Admin</strong> (user management, configuration, reports), <strong>Submit</strong> (politicians declare their assets), <strong>Search</strong> (public search by name/party/position), and <strong>API</strong> (machine-readable data for third-party systems).</p>
      </article>
      <article class="project-article" style="margin-top:16px;">
        <h3>REST API</h3>
        <p>Third-party systems can access all public data via the <a href="api/">REST API</a>. Read endpoints (GET) are public; write endpoints (POST / PUT / DELETE) require an <code>X-API-Key</code> header.</p>
      </article>
    </section>
  </main>
  <footer id="main-footer">
    <p>CEI326 Web Engineering &mdash; Registry of Assets Project</p>
  </footer>
</body>
</html>
