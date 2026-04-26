<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../../auth/login.php'); exit; }
require_once '../../includes/db.php';
$isAdmin = $_SESSION['role'] === 'admin';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current, $user['password_hash'])) {
        $errors[] = 'Current password is incorrect.';
    }
    if (strlen($new) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    }
    if ($new !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (empty($errors)) {
        $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id')
            ->execute([':h' => password_hash($new, PASSWORD_DEFAULT), ':id' => $_SESSION['user_id']]);
        $success = 'Password changed successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Change Password &ndash; CEI326</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Change Password</h1>
      <p class="subtitle">Update Your Account Password</p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../../index.php" class="nav-link">Home</a>
    <a href="../dashboard/dashboard.php" class="nav-link">Dashboard</a>
    <?php if ($isAdmin): ?><a href="../admin/admin_dashboard.php" class="nav-link">Admin</a><?php endif; ?>
    <a href="submit_dashboard.php" class="nav-link">Submit</a>
    <a href="my_profile.php" class="nav-link active">My Profile</a>
    <?php if (!$isAdmin): ?><a href="my_submissions.php" class="nav-link">My Submissions</a><?php endif; ?>
    <a href="../search/search_dashboard.php" class="nav-link">Search</a>
    <a href="../search/statistics.php" class="nav-link">Statistics</a>
    <a href="../../auth/logout.php" class="nav-link nav-logout">Logout</a>
  </nav>
  <main>
    <section>
      <h2>Change Password</h2>
      <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>
      <div class="form-card">
        <form method="POST">
          <div class="form-group">
            <label>Current Password<span style="color:#c0392b;">*</span></label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="form-group">
            <label>New Password<span style="color:#c0392b;">*</span></label>
            <input type="password" name="new_password" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Confirm New Password<span style="color:#c0392b;">*</span></label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Change Password</button>
            <a href="my_profile.php" class="btn btn-secondary">Back</a>
          </div>
        </form>
      </div>
    </section>
  </main>
  <footer id="page-footer"><p>CEI326 Web Engineering 2026 &mdash; Submit Module</p></footer>
</body>
</html>
