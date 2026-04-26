<?php
session_start();

// Already authenticated users skip the login screen.
if (isset($_SESSION['user_id'])) {
    header('Location: ../modules/dashboard/dashboard.php');
    exit;
}

require_once '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email !== '' && $password !== '') {
        $stmt = $pdo->prepare('SELECT id, username, role, password_hash FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Mitigate session fixation by issuing a fresh session id once
            // the credentials have been verified.
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header('Location: ../modules/dashboard/dashboard.php');
            exit;
        }
    }

    $error = 'Invalid credentials. Please check your email and password.';
}

$registered = isset($_GET['registered']) && $_GET['registered'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login &ndash; CEI326</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="auth.css">
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../assets/images/assets.png" alt="Web engineering icon with browser window and code brackets" width="84" height="84">
    <div class="header-text">
      <h1>Sign In</h1>
      <p class="subtitle">Access the project portal</p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../index.php" class="nav-link">Home</a>
    <a href="login.php" class="nav-link active">Login</a>
    <a href="register.php" class="nav-link">Register</a>
    <a href="../modules/search/search_dashboard.php" class="nav-link">Search</a>
    <a href="../modules/search/statistics.php" class="nav-link">Statistics</a>
  </nav>
  <main>
    <section id="form-section">
      <h2>Login</h2>
      <div class="form-card">

        <?php if ($registered): ?>
          <div class="alert alert-success">
            Registration successful! You can now sign in.
          </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
          <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control"
                   placeholder="you@example.com" required autofocus>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <div class="password-field">
              <input type="password" id="password" name="password" class="form-control"
                     placeholder="Your password" required>
              <button type="button" class="password-toggle" data-target="password" aria-label="Show password" title="Show password">👁</button>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Sign In</button>
          </div>
        </form>

        <p class="auth-link">Don't have an account? <a href="register.php">Register here</a></p>
      </div>
    </section>
  </main>
  <footer id="page-footer">
    <p>CEI326 Web Engineering 2026 &ndash; Group Project</p>
  </footer>

  <script>
    // Toggle visibility of password fields when the eye icon is clicked.
    document.querySelectorAll('.password-toggle').forEach(function (button) {
      button.addEventListener('click', function () {
        var input = document.getElementById(button.getAttribute('data-target'));
        if (!input) return;

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
