<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../../auth/login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { header('Location: ../dashboard/dashboard.php'); exit; }
require_once '../../includes/db.php';

$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = in_array($_POST['role'] ?? '', ['admin','politician']) ? $_POST['role'] : 'politician';
        $phone = trim($_POST['phone'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');

        if ($username === '') $errors[] = 'Username is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e');
            $stmt->execute([':e' => $email]);
            if ($stmt->fetch()) { $errors[] = 'Email already registered.'; }
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            // first_name and last_name stored in users for everyone
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, phone, first_name, last_name) VALUES (:u,:e,:h,:r,:p,:fn,:ln)');
            $stmt->execute([':u'=>$username,':e'=>$email,':h'=>$hash,':r'=>$role,':p'=>$phone,':fn'=>$firstName,':ln'=>$lastName]);
            $newId = (int)$pdo->lastInsertId();
            if ($role === 'politician') {
                $pStmt = $pdo->prepare('INSERT INTO politicians (user_id) VALUES (:uid)');
                $pStmt->execute([':uid'=>$newId]);
            }
            $success = 'User added successfully.';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id && $id !== (int)$_SESSION['user_id']) {
            $pdo->prepare('DELETE FROM users WHERE id = :id')->execute([':id'=>$id]);
            $success = 'User deleted.';
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin','politician']) ? $_POST['role'] : 'politician';
        $phone = trim($_POST['phone'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        if ($id && $username !== '') {
            // Everything including first/last name lives in users now
            $pdo->prepare('UPDATE users SET username=:u, role=:r, phone=:p, first_name=:fn, last_name=:ln WHERE id=:id')
                ->execute([':u'=>$username,':r'=>$role,':p'=>$phone,':fn'=>$firstName,':ln'=>$lastName,':id'=>$id]);
            // If role changed to politician, ensure politicians record exists
            if ($role === 'politician') {
                $stmt = $pdo->prepare('SELECT id FROM politicians WHERE user_id=:uid');
                $stmt->execute([':uid'=>$id]);
                if (!$stmt->fetchColumn()) {
                    $pdo->prepare('INSERT INTO politicians (user_id) VALUES (:uid)')->execute([':uid'=>$id]);
                }
            }
            $success = 'User updated.';
        }
    }
}

$users = $pdo->query('SELECT id, username, email, role, phone, first_name, last_name, created_at FROM users ORDER BY created_at DESC')->fetchAll();
$keyword = trim($_GET['keyword'] ?? '');
if ($keyword !== '') {
    $stmt = $pdo->prepare("SELECT id, username, email, role, phone, first_name, last_name, created_at FROM users WHERE username LIKE :k1 OR email LIKE :k2 ORDER BY created_at DESC");
    $stmt->execute([':k1' => '%'.$keyword.'%', ':k2' => '%'.$keyword.'%']);
    $users = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users – Admin</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <?php include $_SERVER['DOCUMENT_ROOT'] . '/dashboard/CSE_326_Project_NEW/includes/header.php'; ?>
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Manage Users</h1>
      <p class="subtitle">Add, Edit or Remove User Accounts</p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../../index.php" class="nav-link">Home</a>
    <a href="../dashboard/dashboard.php" class="nav-link">Dashboard</a>
    <a href="admin_dashboard.php" class="nav-link">Admin</a>
    <a href="manage_users.php" class="nav-link active">Manage Users</a>
    <a href="manage_submissions.php" class="nav-link">Submissions</a>
    <a href="configure_system.php" class="nav-link">Configure</a>
    <a href="reports.php" class="nav-link">Reports</a>
    <a href="../search/search_dashboard.php" class="nav-link">Search</a>
    <a href="../search/statistics.php" class="nav-link">Statistics</a>
    <a href="../../auth/logout.php" class="nav-link nav-logout">Logout</a>
  </nav>
  <main>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <section id="add-section">
      <h2>Add New User</h2>
      <div class="form-card">
        <form method="POST" action="manage_users.php">
          <input type="hidden" name="action" value="add">
          <div class="panel-grid" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
              <label>Username<span class="required-mark">*</span></label>
              <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Email<span class="required-mark">*</span></label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Password<span class="required-mark">*</span></label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Role</label>
              <select name="role" class="form-control">
                <option value="politician">Politician</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input type="text" name="phone" class="form-control">
            </div>
            <div class="form-group">
              <label>First Name</label>
              <input type="text" name="first_name" class="form-control">
            </div>
            <div class="form-group">
              <label>Last Name</label>
              <input type="text" name="last_name" class="form-control">
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn" id="btn-add-user">Add User</button>
          </div>
        </form>
      </div>
    </section>

    <section id="list-section">
      <h2>Registered Users</h2>
      <form method="GET" style="margin-bottom:16px; display:flex; gap:10px;" action="manage_users.php#list-section">
        <input type="text" name="keyword" class="form-control" style="max-width:320px;" placeholder="Search by name or email..." value="<?= htmlspecialchars($keyword) ?>">
        <button type="submit" class="btn">Search</button>
        <?php if ($keyword): ?><a href="manage_users.php#list-section" class="btn btn-secondary">Clear</a><?php endif; ?>
      </form>
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr><th>ID</th><th>Username</th><th>Email</th><th>Phone</th><th>Role</th><th>Registered</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td><?= $u['id'] ?></td>
              <td><?= htmlspecialchars($u['username']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['phone'] ?? '–') ?></td>
              <td><span class="badge badge-role-<?= $u['role'] ?>"><?= htmlspecialchars($u['role']) ?></span></td>
              <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              <td style="white-space:nowrap;vertical-align:middle;">
                <button class="btn btn-secondary" style="padding:6px 12px;font-size:0.82rem;margin-right:6px;"
                  onclick="openEdit(
                    <?= $u['id'] ?>,
                    '<?= htmlspecialchars(addslashes($u['username'])) ?>',
                    '<?= $u['role'] ?>',
                    '<?= htmlspecialchars(addslashes($u['phone'] ?? '')) ?>',
                    '<?= htmlspecialchars(addslashes($u['first_name'] ?? '')) ?>',
                    '<?= htmlspecialchars(addslashes($u['last_name'] ?? '')) ?>'
                  )">Edit</button>
                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                  <form method="POST" style="display:inline;" class="delete-form">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-danger" style="padding:6px 12px;font-size:0.82rem;">Delete</button>
                  </form>
                  <?php else: ?>
                  <button type="button" class="btn btn-danger" style="padding:6px 12px;font-size:0.82rem;visibility:hidden;" disabled>Delete</button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
  <footer id="page-footer"><p>CEI326 Web Engineering 2026 - Admin Module</p></footer>

  <!-- Edit modal -->
  <div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:36px 40px;max-width:480px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <h2 style="font-size:1.3rem;color:#1a3a5c;margin-bottom:20px;border-bottom:2px solid #1a6fa8;padding-bottom:8px;display:inline-block;">Edit User</h2>
      <form method="POST" action="manage_users.php">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="user_id" id="edit-user-id">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" id="edit-username" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Role</label>
          <select name="role" id="edit-role" class="form-control">
            <option value="politician">Politician</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="phone" id="edit-phone" class="form-control">
        </div>
        <div class="form-group">
          <label>First Name</label>
          <input type="text" name="first_name" id="edit-first-name" class="form-control">
        </div>
        <div class="form-group">
          <label>Last Name</label>
          <input type="text" name="last_name" id="edit-last-name" class="form-control">
        </div>
        <div class="form-actions">
          <button type="submit" class="btn">Save Changes</button>
          <button type="button" class="btn btn-secondary" onclick="closeEdit()">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    document.querySelectorAll('.delete-form').forEach(function(form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({
          title: 'Delete this user?',
          text: 'This action cannot be undone.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#c0392b',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes, delete',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) form.submit();
        });
      });
    });

    document.getElementById('btn-add-user').addEventListener('click', function(e) {
      e.preventDefault();
      const form = this.closest('form');
      if (!form.checkValidity()) { form.reportValidity(); return; }
      Swal.fire({
        title: 'Add this user?',
        text: 'A new account will be created.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#27ae60',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, add user',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) form.submit();
      });
    });

    function openEdit(id, username, role, phone, firstName, lastName) {
      document.getElementById('edit-user-id').value = id;
      document.getElementById('edit-username').value = username;
      document.getElementById('edit-role').value = role;
      document.getElementById('edit-phone').value = phone;
      document.getElementById('edit-first-name').value = firstName;
      document.getElementById('edit-last-name').value = lastName;
      document.getElementById('edit-modal').style.display = 'flex';
    }
    function closeEdit() {
      document.getElementById('edit-modal').style.display = 'none';
    }
  </script>
</body>
</html>