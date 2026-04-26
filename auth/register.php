<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: ../modules/dashboard/dashboard.php');
    exit;
}

require_once '../includes/db.php';

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $old = compact('username', 'email', 'first_name', 'last_name');

    if ($username === '') {
        $errors[] = 'Username is required.';
    }
    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'That email address is already registered.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Create the authenticated account. New self-registrations are
        // always created with the 'politician' role; admins are seeded
        // or promoted via the admin user-management screen.
        $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role, first_name, last_name)
             VALUES (:u, :e, :h, "politician", :fn, :ln)'
        )->execute([
            ':u' => $username,
            ':e' => $email,
            ':h' => $hash,
            ':fn' => $first_name !== '' ? $first_name : $username,
            ':ln' => $last_name,
        ]);
        $newId = (int)$pdo->lastInsertId();

        // Every politician account gets an empty politician profile so
        // they can immediately fill in party / position / district from
        // the Submit module.
        $pdo->prepare('INSERT INTO politicians (user_id) VALUES (:uid)')
            ->execute([':uid' => $newId]);

        header('Location: login.php?registered=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register &ndash; CEI326</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="auth.css">
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Create Account</h1>
      <p class="subtitle">Register to access the portal</p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../index.php" class="nav-link">Home</a>
    <a href="login.php" class="nav-link">Login</a>
    <a href="register.php" class="nav-link active">Register</a>
    <a href="../modules/search/search_dashboard.php" class="nav-link">Search</a>
    <a href="../modules/search/statistics.php" class="nav-link">Statistics</a>
  </nav>
  <main>
    <section id="form-section">
      <h2>New Account</h2>
      <div class="form-card">
        <?php if (!empty($errors)): ?>
          <div class="alert alert-error">
            <strong>Please fix the following errors:</strong>
            <ul>
              <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <form method="POST" action="register.php">
          <div class="form-group">
            <label for="username">Username<span class="required-mark">*</span></label>
            <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($old['username'] ?? '') ?>" placeholder="Your display name" required>
          </div>
          <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($old['first_name'] ?? '') ?>" placeholder="Your first name">
          </div>
          <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($old['last_name'] ?? '') ?>" placeholder="Your last name">
          </div>
          <div class="form-group">
            <label for="email">Email Address<span class="required-mark">*</span></label>
            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="you@example.com" required>
          </div>
          <div class="form-group">
            <label for="password">Password <small>(min. 8 characters)</small><span class="required-mark">*</span></label>
            <div class="password-field">
              <input type="password" id="password" name="password" class="form-control" placeholder="Choose a strong password" required>
              <button type="button" class="password-toggle" data-target="password" aria-label="Show password">👁</button>
            </div>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirm Password<span class="required-mark">*</span></label>
            <div class="password-field">
              <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Repeat your password" required>
              <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show password">👁</button>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Register</button>
          </div>
        </form>
        <p class="auth-link">Already have an account? <a href="login.php">Sign in here</a></p>
      </div>
    </section>
  </main>
  <footer id="page-footer"><p>CEI326 Web Engineering 2026 &ndash; Group Project</p></footer>
  <script>
    // Toggle visibility of password fields when the eye icon is clicked.
    document.querySelectorAll('.password-toggle').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var input = document.getElementById(btn.getAttribute('data-target'));
        if (!input) return;
        if (input.type === 'password') {
          input.type = 'text';
          btn.setAttribute('aria-label', 'Hide password');
        } else {
          input.type = 'password';
          btn.setAttribute('aria-label', 'Show password');
        }
      });
    });
  </script>
</body>
</html>
