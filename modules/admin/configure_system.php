<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../../auth/login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { header('Location: ../dashboard/dashboard.php'); exit; }
require_once '../../includes/db.php';

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $entity = $_POST['entity'] ?? '';

    if ($action === 'add') {
        if ($entity === 'party') {
            $name = trim($_POST['name'] ?? '');
            $short = trim($_POST['short_name'] ?? '');
            if ($name === '') {
                $errors[] = 'Party name is required.';
            } else {
                $pdo->prepare('INSERT INTO parties (name, short_name) VALUES (:n, :s)')
                    ->execute([':n' => $name, ':s' => $short]);
                $success = 'Party added.';
            }
        } elseif ($entity === 'position') {
            $title = trim($_POST['title'] ?? '');
            if ($title === '') {
                $errors[] = 'Position title is required.';
            } else {
                $pdo->prepare('INSERT INTO positions (title) VALUES (:t)')->execute([':t' => $title]);
                $success = 'Position added.';
            }
        } elseif ($entity === 'district') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $errors[] = 'District name required.';
            } else {
                $pdo->prepare('INSERT INTO districts (name) VALUES (:n)')->execute([':n' => $name]);
                $success = 'District added.';
            }
        } elseif ($entity === 'committee') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            if ($name === '') {
                $errors[] = 'Committee name is required.';
            } else {
                $pdo->prepare('INSERT INTO committees (name, description) VALUES (:n, :d)')
                    ->execute([':n' => $name, ':d' => $description !== '' ? $description : null]);
                $success = 'Committee added.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['item_id'] ?? 0);
        if ($id) {
            if ($entity === 'party') {
                $pdo->prepare('DELETE FROM parties WHERE id = :id')->execute([':id' => $id]);
                $success = 'Party deleted.';
            } elseif ($entity === 'position') {
                $pdo->prepare('DELETE FROM positions WHERE id = :id')->execute([':id' => $id]);
                $success = 'Position deleted.';
            } elseif ($entity === 'district') {
                $pdo->prepare('DELETE FROM districts WHERE id = :id')->execute([':id' => $id]);
                $success = 'District deleted.';
            } elseif ($entity === 'committee') {
                $pdo->prepare('DELETE FROM committees WHERE id = :id')->execute([':id' => $id]);
                $success = 'Committee deleted.';
            }
        }
    }
}

$parties = $pdo->query('SELECT * FROM parties ORDER BY name')->fetchAll();
$positions = $pdo->query('SELECT * FROM positions ORDER BY title')->fetchAll();
$districts = $pdo->query('SELECT * FROM districts ORDER BY name')->fetchAll();
$committees = $pdo->query(
    'SELECT c.*,
            (SELECT COUNT(*) FROM politician_committees pc WHERE pc.committee_id = c.id) AS member_count
       FROM committees c
   ORDER BY c.name'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Configure System &ndash; Admin</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <header id="page-header">
    <img class="header-logo" src="../../assets/images/assets.png" alt="logo" width="84" height="84">
    <div class="header-text">
      <h1>Configure System</h1>
      <p class="subtitle">Parties, Positions, Districts, Committees &amp; System Settings</p>
    </div>
  </header>
  <nav id="main-nav">
    <a href="../../index.php" class="nav-link">Home</a>
    <a href="../dashboard/dashboard.php" class="nav-link">Dashboard</a>
    <a href="admin_dashboard.php" class="nav-link">Admin</a>
    <a href="manage_users.php" class="nav-link">Manage Users</a>
    <a href="manage_submissions.php" class="nav-link">Submissions</a>
    <a href="configure_system.php" class="nav-link active">Configure</a>
    <a href="reports.php" class="nav-link">Reports</a>
    <a href="../search/search_dashboard.php" class="nav-link">Search</a>
    <a href="../search/statistics.php" class="nav-link">Statistics</a>
    <a href="../../auth/logout.php" class="nav-link nav-logout">Logout</a>
  </nav>
  <main>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <!-- Parties -->
    <section>
      <h2>Parties</h2>
      <div class="form-card" style="margin-bottom:24px;">
        <form method="POST" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="entity" value="party">
          <div class="form-group" style="margin:0;flex:1;min-width:200px;">
            <label>Party Full Name<span class="required-mark">*</span></label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="form-group" style="margin:0;min-width:140px;">
            <label>Short Name</label>
            <input type="text" name="short_name" class="form-control">
          </div>
          <button type="submit" class="btn" style="margin-top:24px;">Add Party</button>
        </form>
      </div>
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>ID</th><th>Name</th><th>Short Name</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($parties as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td><?= htmlspecialchars($p['short_name'] ?? '–') ?></td>
              <td>
                <form method="POST" style="display:inline;" class="delete-form">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="entity" value="party">
                  <input type="hidden" name="item_id" value="<?= (int)$p['id'] ?>">
                  <button type="submit" class="btn btn-danger" style="padding:6px 12px;font-size:0.82rem;">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Positions -->
    <section>
      <h2>Positions</h2>
      <div class="form-card" style="margin-bottom:24px;">
        <form method="POST" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="entity" value="position">
          <div class="form-group" style="margin:0;flex:1;min-width:200px;">
            <label>Position Title<span class="required-mark">*</span></label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <button type="submit" class="btn" style="margin-top:24px;">Add Position</button>
        </form>
      </div>
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>ID</th><th>Title</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($positions as $pos): ?>
            <tr>
              <td><?= (int)$pos['id'] ?></td>
              <td><?= htmlspecialchars($pos['title']) ?></td>
              <td>
                <form method="POST" style="display:inline;" class="delete-form">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="entity" value="position">
                  <input type="hidden" name="item_id" value="<?= (int)$pos['id'] ?>">
                  <button type="submit" class="btn btn-danger" style="padding:6px 12px;font-size:0.82rem;">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Districts -->
    <section>
      <h2>Districts</h2>
      <div class="form-card" style="margin-bottom:24px;">
        <form method="POST" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="entity" value="district">
          <div class="form-group" style="margin:0;flex:1;min-width:200px;">
            <label>District Name<span class="required-mark">*</span></label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <button type="submit" class="btn" style="margin-top:24px;">Add District</button>
        </form>
      </div>
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>ID</th><th>Name</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($districts as $dist): ?>
            <tr>
              <td><?= (int)$dist['id'] ?></td>
              <td><?= htmlspecialchars($dist['name']) ?></td>
              <td>
                <form method="POST" style="display:inline;" class="delete-form">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="entity" value="district">
                  <input type="hidden" name="item_id" value="<?= (int)$dist['id'] ?>">
                  <button type="submit" class="btn btn-danger" style="padding:6px 12px;font-size:0.82rem;">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Committees -->
    <section>
      <h2>Committees</h2>
      <div class="form-card" style="margin-bottom:24px;">
        <form method="POST" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="entity" value="committee">
          <div class="form-group" style="margin:0;flex:1;min-width:200px;">
            <label>Committee Name<span class="required-mark">*</span></label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="form-group" style="margin:0;flex:2;min-width:240px;">
            <label>Description</label>
            <input type="text" name="description" class="form-control" placeholder="Short description (optional)">
          </div>
          <button type="submit" class="btn" style="margin-top:24px;">Add Committee</button>
        </form>
      </div>
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Members</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($committees as $c): ?>
            <tr>
              <td><?= (int)$c['id'] ?></td>
              <td><?= htmlspecialchars($c['name']) ?></td>
              <td><?= htmlspecialchars($c['description'] ?? '–') ?></td>
              <td><?= (int)$c['member_count'] ?></td>
              <td>
                <form method="POST" style="display:inline;" class="delete-form" data-type="committee">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="entity" value="committee">
                  <input type="hidden" name="item_id" value="<?= (int)$c['id'] ?>">
                  <button type="submit" class="btn btn-danger" style="padding:6px 12px;font-size:0.82rem;">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($committees)): ?>
              <tr><td colspan="5" style="text-align:center;color:#888;padding:18px;">No committees defined yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
  <footer id="page-footer"><p>CEI326 Web Engineering 2026 &mdash; Admin Module</p></footer>

  <script>
    document.querySelectorAll('.delete-form').forEach(function(form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        const isCommittee = form.dataset.type === 'committee';
        Swal.fire({
          title: 'Are you sure?',
          text: isCommittee
            ? 'This action cannot be undone. Memberships will also be removed.'
            : 'This action cannot be undone.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#c0392b',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes, delete',
          cancelButtonText: 'Cancel'
        }).then(function(result) {
          if (result.isConfirmed) form.submit();
        });
      });
    });
  </script>
</body>
</html>