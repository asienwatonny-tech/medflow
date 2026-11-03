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
      // tell the next waiting patient at this stage that they are next
      $nx = $db->prepare("SELECT vs.visit_id, st.name FROM visit_stages vs JOIN stages st ON st.id=vs.stage_id
                          WHERE vs.stage_id=? AND vs.status='waiting' ORDER BY vs.queued_at LIMIT 1");
      $nx->execute([$row['stage_id']]);
      if ($w = $nx->fetch()) {
        $msg = 'You are next at ' . $w['name'] . '. Please be ready.';
        $dup = $db->prepare("SELECT COUNT(*) FROM notifications WHERE visit_id=? AND message=?");
        $dup->execute([$w['visit_id'], $msg]);
        if (!$dup->fetchColumn())
          $db->prepare("INSERT INTO notifications (visit_id,message) VALUES (?,?)")->execute([$w['visit_id'], $msg]);
      }
    }
  }
  header('Location: /medflow/staff/queue.php'); exit;
}

$q = $db->prepare("SELECT vs.id, vs.status, vs.queued_at, v.queue_code, p.full_name, st.name AS stage
  FROM visit_stages vs JOIN stages st ON st.id=vs.stage_id
  JOIN visits v ON v.id=vs.visit_id JOIN patients p ON p.id=v.patient_id
  WHERE st.role=? AND vs.status IN ('waiting','in_service') ORDER BY vs.queued_at");
$q->execute([$role]); $list = $q->fetchAll();
$labels = ['nurse'=>'Nurse / Triage','doctor'=>'Doctor','lab'=>'Laboratory','pharmacy'=>'Pharmacy'];
$title = 'My queue';
require '../includes/header.php';
?>
<div class="card">
  <div class="qhead">
    <div>
      <h2><?= e($labels[$role] ?? ucfirst($role)) ?> queue</h2>
      <p class="muted">Signed in as <?= e($_SESSION['user']['name']) ?>. This list refreshes every 20 seconds.</p>
    </div>
    <div class="count"><?= count($list) ?><small>in queue</small></div>
  </div>

  <table>
    <thead><tr><th>#</th><th>Queue ID</th><th>Patient</th><th>Waiting since</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($list as $i => $r): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><b><?= e($r['queue_code']) ?></b></td>
        <td><?= e($r['full_name']) ?></td>
        <td><?= e(date('H:i', strtotime($r['queued_at']))) ?></td>
        <td><span class="badge b-<?= e($r['status']) ?>"><?= e(ucfirst(str_replace('_',' ',$r['status']))) ?></span></td>
        <td>
          <form method="post" class="actions">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <?php if ($r['status'] === 'waiting'): ?>
              <button name="action" value="start">Start</button>
            <?php else: ?>
              <button name="action" value="complete" class="btn-ok">Complete</button>
            <?php endif; ?>
            <?php if ($role === 'lab'): ?><button name="action" value="skip" class="btn-ghost">Skip</button><?php endif; ?>
          </form>
        </td>
      </tr>
    <?php endforeach; if (!$list) echo '<tr><td colspan="6" class="empty">No patients waiting. New patients will appear here.</td></tr>'; ?>
    </tbody>
  </table>
</div>
<script>setTimeout(function(){ location.reload(); }, 20000);</script>
<?php require '../includes/footer.php'; ?>