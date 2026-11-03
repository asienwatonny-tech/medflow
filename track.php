<?php
require 'config/db.php';
require 'includes/estimate.php';
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$data = null; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $s = $db->prepare("SELECT v.id, v.queue_code, p.full_name, p.phone FROM visits v JOIN patients p ON p.id=v.patient_id WHERE v.queue_code=?");
  $s->execute([strtoupper(trim($_POST['code']))]);
  $v = $s->fetch();
  if ($v && substr($v['phone'], -4) === trim($_POST['pin'])) {
    $data = ['v' => $v, 'j' => journey($db, (int)$v['id'])];
    $n = $db->prepare("SELECT message,created_at FROM notifications WHERE visit_id=? ORDER BY id DESC LIMIT 3");
    $n->execute([$v['id']]); $data['n'] = $n->fetchAll();
  } else $err = 'Queue ID or phone digits not correct.';
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Track My Visit</title></head>
<body style="font-family:sans-serif;max-width:520px;margin:30px auto;padding:0 12px">
<h2>MedFlow: Track my visit</h2>
<?php if (!$data): ?>
<p style="color:red"><?= e($err) ?></p>
<form method="post">
  <input name="code" placeholder="Queue ID e.g. Q-0001" required style="width:100%;padding:8px;margin:4px 0"><br>
  <input name="pin" placeholder="Last 4 digits of phone" maxlength="4" required style="width:100%;padding:8px;margin:4px 0"><br>
  <button style="padding:8px 16px">Track</button>
</form>
<?php else: ?>
<p><b>Patient:</b> <?= e($data['v']['full_name']) ?><br><b>Queue ID:</b> <?= e($data['v']['queue_code']) ?></p>
<table border="1" cellpadding="8" cellspacing="0" width="100%">
<tr><th>Stage</th><th>Status</th><th>Information</th></tr>
<?php foreach ($data['j']['stages'] as $s): ?>
<tr><td><?= e($s['name']) ?></td><td><?= e(ucfirst(str_replace('_',' ',$s['status']))) ?></td>
<td><?php
 if (in_array($s['status'],['waiting','in_service'])) echo e($s['ahead']).' ahead, about '.e($s['est']).' min';
 elseif ($s['status']==='pending') echo 'About '.e($s['est']).' min';
 else echo '-'; ?></td></tr>
<?php endforeach; ?>
</table>
<p><b>Estimated remaining time: about <?= (int)floor($data['j']['remaining']/60) ?>h <?= $data['j']['remaining']%60 ?>m</b><br>
<small>Estimates are approximate, not guaranteed.</small></p>
<?php foreach ($data['n'] as $n): ?><p>🔔 <?= e($n['message']) ?> <small>(<?= e($n['created_at']) ?>)</small></p><?php endforeach; ?>
<form method="post" id="refreshForm">
  <input type="hidden" name="code" value="<?= e($data['v']['queue_code']) ?>">
  <input type="hidden" name="pin" value="<?= e(substr($data['v']['phone'], -4)) ?>">
  <button style="padding:8px 16px">Refresh now</button>
</form>
<p><small>This page updates automatically every 15 seconds.</small></p>
<script>
  setTimeout(function () { document.getElementById('refreshForm').submit(); }, 15000);
</script>
<?php endif; ?>
</body></html>