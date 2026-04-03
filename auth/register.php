<?php
// Session
session_start();

// Redirect
if (isset($_SESSION['user_id'])) {
    header('Location: ../modules/dashboard/dashboard.php');
    exit;
}

// Dependencies
require_once '../includes/db.php';

// Form state
$errors = [];
$old    = [];

// Form handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $old = compact('username', 'email');

    if ($username === '') {
        $errors[] = 'Username is required.';
    }

    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($confirm === '') {
        $errors[] = 'Please confirm your password.';
    } elseif ($password !== $confirm) {
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
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role) VALUES (:u, :e, :h, :r)'
        );
        $stmt->execute([
            ':u' => $username,
            ':e' => $email,
            ':h' => $hash,
            ':r' => 'user',
        ]);

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
  <title>Register – CEI326</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="auth.css">
  <style>
    .required-mark {
      color: #c0392b;
      margin-left: 2px;
      font-weight: 700;
    }

    .password-field {
      position: relative;
    }

    .password-field .form-control {
      padding-right: 52px;
    }

    .password-toggle {
      position: absolute;
      top: 50%;
      right: 12px;
      transform: translateY(-50%);
      border: none;
      background: transparent;
      cursor: pointer;
      font-size: 1.05rem;
      line-height: 1;
      color: #666;
      padding: 4px;
    }

    .password-toggle:hover {
      color: #2c3e50;
    }

    .password-toggle:focus {
      outline: 2px solid #3498db;
      outline-offset: 2px;
      border-radius: 4px;
    }
  </style>
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../assets/images/assets.png" alt="Web engineering icon with browser window and code brackets" width="84" height="84">
    <div class="header-text">
      <h1>Create Account</h1>
      <p class="subtitle">Register to access the portal</p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../index.php" class="nav-link">Home</a>
    <a href="login.php" class="nav-link">Login</a>
    <a href="register.php" class="nav-link active">Register</a>
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
            <label for="username">Username<span class="required-mark" aria-hidden="true">*</span></label>
            <input type="text" id="username" name="username" class="form-control"
                   value="<?= htmlspecialchars($old['username'] ?? '') ?>"
                   placeholder="Your display name" required>
          </div>

          <div class="form-group">
            <label for="email">Email Address<span class="required-mark" aria-hidden="true">*</span></label>
            <input type="email" id="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                   placeholder="you@example.com" required>
          </div>

          <div class="form-group">
            <label for="password">Password <small>(min. 8 characters)</small><span class="required-mark" aria-hidden="true">*</span></label>
            <div class="password-field">
              <input type="password" id="password" name="password" class="form-control"
                     placeholder="Choose a strong password" required>
              <button type="button" class="password-toggle" data-target="password" aria-label="Show password" title="Show password">👁</button>
            </div>
          </div>

          <div class="form-group">
            <label for="confirm_password">Confirm Password<span class="required-mark" aria-hidden="true">*</span></label>
            <div class="password-field">
              <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                     placeholder="Repeat your password" required>
              <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show password" title="Show password">👁</button>
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
  <footer id="page-footer">
    <p>CEI326 Web Engineering 2026 – Group Project</p>
  </footer>

  <script>
    const passwordToggleButtons = document.querySelectorAll('.password-toggle');

    passwordToggleButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        const targetId = button.getAttribute('data-target');
        const input = document.getElementById(targetId);

        if (!input) {
          return;
        }

        if (input.type === 'password') {
          input.type = 'text';
          button.setAttribute('aria-label', 'Hide password');
          button.setAttribute('title', 'Hide password');
        } else {
          input.type = 'password';
          button.setAttribute('aria-label', 'Show password');
          button.setAttribute('title', 'Show password');
        }
      });
    });
  </script>
</body>
</html>