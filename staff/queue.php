<?php
require '../includes/auth.php';
require_role(['nurse','doctor','lab','pharmacy']);
$uid = $_SESSION['user']['id']; $role = $_SESSION['user']['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int)$_POST['id']; $action = $_POST['action'];
  $own = $db->prepare("SELECT vs.*, st.seq, st.role FROM visit_stages vs JOIN stages st ON st.id=vs.stage_id WHERE vs.id=?");
  $own->execute([$id]); $row = $own->fetch();
  if ($row && $row['role'] === $role) {                       // role-based access check
    $now = date('Y-m-d H:i:s');
    if ($action === 'start') {
      $db->prepare("UPDATE visit_stages SET status='in_service',started_at=?,updated_by=? WHERE id=? AND status='waiting'")->execute([$now,$uid,$id]);
    } elseif ($action === 'complete' || $action === 'skip') {
      $new = $action === 'skip' ? 'skipped' : 'completed';
      $db->prepare("UPDATE visit_stages SET status=?,completed_at=?,updated_by=? WHERE id=?")->execute([$new,$now,$uid,$id]);
      // move patient to next stage
      $n = $db->prepare("SELECT vs.id FROM visit_stages vs JOIN stages st ON st.id=vs.stage_id
                         WHERE vs.visit_id=? AND st.seq>? AND vs.status='pending' ORDER BY st.seq LIMIT 1");
      $n->execute([$row['visit_id'], $row['seq']]);
      $next = $n->fetchColumn();
      if ($next) {
        $db->prepare("UPDATE visit_stages SET status='waiting',queued_at=? WHERE id=?")->execute([$now,$next]);
        $db->prepare("INSERT INTO notifications (visit_id,message) VALUES (?,?)")
           ->execute([$row['visit_id'], 'You have moved to the next stage. Check your journey.']);
      } else {
        $db->prepare("UPDATE visits SET status='completed' WHERE id=?")->execute([$row['visit_id']]);
        $db->prepare("INSERT INTO notifications (visit_id,message) VALUES (?,?)")
           ->execute([$row['visit_id'], 'Your visit is complete. Thank you.']);
      }
    }
  }
  header('Location: queue.php'); exit;
}

$q = $db->prepare("SELECT vs.id, vs.status, vs.queued_at, v.queue_code, p.full_name, st.name AS stage, st.seq
  FROM visit_stages vs JOIN stages st ON st.id=vs.stage_id
  JOIN visits v ON v.id=vs.visit_id JOIN patients p ON p.id=v.patient_id
  WHERE st.role=? AND vs.status IN ('waiting','in_service') ORDER BY vs.queued_at");
$q->execute([$role]); $list = $q->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta http-equiv="refresh" content="20"><title>Queue</title></head>
<body style="font-family:sans-serif;max-width:720px;margin:30px auto">
<h2><?= e(ucfirst($role)) ?> queue</h2>
<table border="1" cellpadding="8" cellspacing="0" width="100%">
<tr><th>Queue ID</th><th>Patient</th><th>Stage</th><th>Status</th><th>Action</th></tr>
<?php foreach ($list as $r): ?>
<tr><td><?= e($r['queue_code']) ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['stage']) ?></td><td><?= e($r['status']) ?></td>
<td><form method="post" style="display:inline"><input type="hidden" name="id" value="<?= $r['id'] ?>">
<?php if ($r['status']==='waiting'): ?><button name="action" value="start">Start</button>
<?php else: ?><button name="action" value="complete">Complete</button><?php endif; ?>
<?php if ($role==='lab'): ?><button name="action" value="skip">Skip</button><?php endif; ?>
</form></td></tr>
<?php endforeach; if (!$list) echo '<tr><td colspan="5">No patients waiting</td></tr>'; ?>
</table>
<p><a href="../logout.php">Logout</a></p></body></html>