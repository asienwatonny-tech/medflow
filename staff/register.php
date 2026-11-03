<?php
require '../includes/auth.php';
require_role(['receptionist','admin']);
$msg = ''; $ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['full_name']); $phone = trim($_POST['phone']);
  if ($name === '' || !preg_match('/^[0-9+]{9,15}$/', $phone)) {
    $msg = 'Enter a valid name and a phone number of 9 to 15 digits.';
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
    $ok = true; $msg = $code;
  }
}
$recent = $db->query("SELECT v.queue_code, p.full_name, v.created_at FROM visits v JOIN patients p ON p.id=v.patient_id ORDER BY v.id DESC LIMIT 5")->fetchAll();
$title = 'Register patient';
require '../includes/header.php';
?>
<div class="card">
  <h2>Register patient</h2>
  <p class="muted">Create a visit and give the patient their Queue ID.</p>

  <?php if ($ok): ?>
    <div class="msg ok">
      Patient registered. Queue ID: <span class="qid"><?= e($msg) ?></span><br>
      <small>Tell the patient to keep this ID and the last 4 digits of their phone to track their visit.</small>
    </div>
  <?php elseif ($msg): ?>
    <div class="msg err"><?= e($msg) ?></div>
  <?php endif; ?>

  <form method="post">
    <label>Full name</label>
    <input name="full_name" placeholder="e.g. Jane Wanjiku" required>
    <label>Phone number</label>
    <input name="phone" placeholder="e.g. 0712345678" inputmode="numeric" required>
    <button>Register and create Queue ID</button>
  </form>
</div>

<div class="card">
  <h3>Recent registrations</h3>
  <table>
    <thead><tr><th>Queue ID</th><th>Patient</th><th>Registered</th></tr></thead>
    <tbody>
    <?php foreach ($recent as $r): ?>
      <tr><td><b><?= e($r['queue_code']) ?></b></td><td><?= e($r['full_name']) ?></td><td><?= e($r['created_at']) ?></td></tr>
    <?php endforeach; if (!$recent) echo '<tr><td colspan="3" class="empty">No patients registered yet</td></tr>'; ?>
    </tbody>
  </table>
</div>
<?php require '../includes/footer.php'; ?>