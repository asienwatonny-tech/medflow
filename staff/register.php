<?php
require '../includes/auth.php';
require_role(['receptionist','admin']);
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['full_name']); $phone = trim($_POST['phone']);
  if ($name === '' || !preg_match('/^[0-9+]{9,15}$/', $phone)) {
    $msg = 'Enter a valid name and phone number.';
  } else {
    $db->beginTransaction();
    $db->prepare("INSERT INTO patients (full_name,phone) VALUES (?,?)")->execute([$name,$phone]);
    $pid = $db->lastInsertId();
    $db->prepare("INSERT INTO visits (patient_id) VALUES (?)")->execute([$pid]);
    $vid = $db->lastInsertId();
    $code = 'Q-' . str_pad($vid, 4, '0', STR_PAD_LEFT);
    $db->prepare("UPDATE visits SET queue_code=? WHERE id=?")->execute([$code,$vid]);
    $stages = $db->query("SELECT id,seq FROM stages ORDER BY seq")->fetchAll();
    $ins = $db->prepare("INSERT INTO visit_stages (visit_id,stage_id,status,queued_at,started_at,completed_at,updated_by) VALUES (?,?,?,?,?,?,?)");
    foreach ($stages as $st) {
      $now = date('Y-m-d H:i:s');
      if ($st['seq'] == 1)      $ins->execute([$vid,$st['id'],'completed',$now,$now,$now,$_SESSION['user']['id']]);
      elseif ($st['seq'] == 2)  $ins->execute([$vid,$st['id'],'waiting',$now,null,null,null]);
      else                      $ins->execute([$vid,$st['id'],'pending',null,null,null,null]);
    }
    $db->commit();
    $msg = "Registered. Queue ID: <b>" . e($code) . "</b>";
  }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Register Patient</title></head>
<body style="font-family:sans-serif;max-width:480px;margin:40px auto">
<h2>Register Patient</h2><p><?= $msg ?></p>
<form method="post">
  <input name="full_name" placeholder="Full name" required style="width:100%;padding:8px;margin:4px 0"><br>
  <input name="phone" placeholder="Phone e.g. 0712345678" required style="width:100%;padding:8px;margin:4px 0"><br>
  <button style="padding:8px 16px">Register and create queue ID</button>
</form>
<p><a href="../logout.php">Logout</a></p></body></html>