<?php
require 'includes/auth.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $s = $db->prepare("SELECT * FROM staff WHERE username=? AND active=1");
  $s->execute([$_POST['username'] ?? '']);
  $u = $s->fetch();
  if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user'] = ['id'=>$u['id'],'name'=>$u['full_name'],'role'=>$u['role']];
    header('Location: /medflow/' . ($u['role']==='admin' ? 'admin/reports.php' : ($u['role']==='receptionist' ? 'staff/register.php' : 'staff/queue.php')));
    exit;
  }
  $err = 'Invalid username or password';
}
$title = 'Staff login';
require 'includes/header.php';
?>
<div class="hero">
  <img src="/medflow/assets/img/ambulance.svg" alt="">
  <div>
    <h1>Smarter patient flow,<br>better healthcare</h1>
    <p>Know where you are in your hospital visit and how long it will take.</p>
  </div>
</div>

<div class="card" style="max-width:420px;margin:0 auto">
  <h2>Staff login</h2>
  <?php if ($err): ?><div class="msg err"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <label>Username</label><input name="username" required>
    <label>Password</label><input name="password" type="password" required>
    <button>Login</button>
  </form>
  <p><a href="track.php">Patient? Track your visit</a></p>
</div>

<?php require 'includes/footer.php'; ?>