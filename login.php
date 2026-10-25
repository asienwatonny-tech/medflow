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
    header('Location: ' . ($u['role']==='admin' ? 'admin/reports.php' : ($u['role']==='receptionist' ? 'staff/register.php' : 'staff/queue.php')));
    exit;
  }
  $err = 'Invalid username or password';
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MedFlow Login</title></head>
<body style="font-family:sans-serif;max-width:360px;margin:60px auto">
<h2>MedFlow Staff Login</h2><p style="color:red"><?= e($err) ?></p>
<form method="post">
  <input name="username" placeholder="Username" required style="width:100%;padding:8px;margin:4px 0"><br>
  <input name="password" type="password" placeholder="Password" required style="width:100%;padding:8px;margin:4px 0"><br>
  <button style="padding:8px 16px">Login</button>
</form>
<p><a href="track.php">Patient? Track your visit</a></p>
</body></html>