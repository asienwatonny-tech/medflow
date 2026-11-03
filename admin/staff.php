<?php
require '../includes/auth.php';
require_role(['admin']);
$msg = ''; $ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (($_POST['action'] ?? '') === 'add') {
    $name = trim($_POST['full_name']); $user = trim($_POST['username']);
    $role = $_POST['role']; $pass = $_POST['password'];
    $roles = ['receptionist','nurse','doctor','lab','pharmacy','admin'];
    if ($name === '' || !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $user) || !in_array($role, $roles) || strlen($pass) < 8) {
      $msg = 'Check the details: username 3-30 letters or numbers, password at least 8 characters.';
    } else {
      try {
        $db->prepare("INSERT INTO staff (full_name,username,password_hash,role) VALUES (?,?,?,?)")
           ->execute([$name, $user, password_hash($pass, PASSWORD_DEFAULT), $role]);
        $msg = 'Staff account created for ' . $name . '.'; $ok = true;
      } catch (PDOException $ex) { $msg = 'That username already exists. Choose another.'; }
    }
  } elseif (($_POST['action'] ?? '') === 'toggle') {
    $id = (int)$_POST['id'];
    if ($id !== (int)$_SESSION['user']['id']) {            // admin cannot deactivate themselves
      $db->prepare("UPDATE staff SET active = 1 - active WHERE id=?")->execute([$id]);
    }
  }
}
$staff = $db->query("SELECT id,full_name,username,role,active FROM staff ORDER BY active DESC, role, full_name")->fetchAll();
$title = 'Manage staff';
require '../includes/header.php';
?>
<div class="card">
  <h2>Add staff</h2>
  <p class="muted">Create an account for a new member of staff. Passwords must be at least 8 characters.</p>
  <?php if ($msg): ?><div class="msg <?= $ok ? 'ok' : 'err' ?>"><?= e($msg) ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="action" value="add">
    <div class="form-row">
      <div><label>Full name</label><input name="full_name" placeholder="e.g. Mary Achieng" required autocomplete="off"></div>
      <div><label>Username</label><input name="username" placeholder="e.g. mary_a" required autocomplete="off"></div>
      <div><label>Password</label><input name="password" type="password" placeholder="8+ characters" required autocomplete="new-password"></div>
      <div><label>Role</label>
        <select name="role">
          <option value="receptionist">Receptionist</option>
          <option value="nurse">Nurse / Triage</option>
          <option value="doctor">Doctor</option>
          <option value="lab">Laboratory</option>
          <option value="pharmacy">Pharmacy</option>
          <option value="admin">Administrator</option>
        </select>
      </div>
    </div>
    <button>Add staff member</button>
  </form>
</div>

<div class="card">
  <h2>Staff accounts <small class="muted">(<?= count($staff) ?>)</small></h2>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($staff as $s): ?>
      <tr<?= $s['active'] ? '' : ' class="off"' ?>>
        <td><b><?= e($s['full_name']) ?></b></td>
        <td><?= e($s['username']) ?></td>
        <td><span class="role"><?= e(ucfirst($s['role'])) ?></span></td>
        <td><span class="badge <?= $s['active'] ? 'b-completed' : 'b-skipped' ?>"><?= $s['active'] ? 'Active' : 'Deactivated' ?></span></td>
        <td>
          <?php if ((int)$s['id'] !== (int)$_SESSION['user']['id']): ?>
            <form method="post" class="actions">
              <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
              <button class="<?= $s['active'] ? 'btn-danger' : 'btn-ok' ?>"><?= $s['active'] ? 'Deactivate' : 'Activate' ?></button>
            </form>
          <?php else: ?><span class="muted">(you)</span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php require '../includes/footer.php'; ?>