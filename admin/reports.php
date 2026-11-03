
<?php
require '../includes/auth.php';
require_role(['admin']);
$perStage = $db->query("SELECT st.name,
  ROUND(AVG(TIMESTAMPDIFF(MINUTE,vs.queued_at,vs.started_at)),1) AS avg_wait,
  ROUND(AVG(TIMESTAMPDIFF(MINUTE,vs.started_at,vs.completed_at)),1) AS avg_service,
  COALESCE(SUM(vs.status IN ('waiting','in_service')),0) AS in_queue,
  COALESCE(SUM(vs.status='completed'),0) AS done
  FROM stages st LEFT JOIN visit_stages vs ON vs.stage_id=st.id GROUP BY st.id ORDER BY st.seq")->fetchAll();
$tot = $db->query("SELECT COUNT(*) total, COALESCE(SUM(status='completed'),0) done, COALESCE(SUM(status='active'),0) active FROM visits")->fetch();
$maxQ = max(array_map(fn($r) => (int)$r['in_queue'], $perStage));
$title = 'Reports';
require '../includes/header.php';
?>
<div class="card">
  <h2>Hospital overview</h2>
  <p class="muted">Live figures from all patient visits.</p>
  <div class="stats">
    <div class="stat"><small>Total visits</small><b><?= (int)$tot['total'] ?></b></div>
    <div class="stat"><small>Completed</small><b><?= (int)$tot['done'] ?></b></div>
    <div class="stat"><small>Active now</small><b><?= (int)$tot['active'] ?></b></div>
    <div class="stat hot"><small>Waiting right now</small><b><?= array_sum(array_map(fn($r) => (int)$r['in_queue'], $perStage)) ?></b></div>
  </div>
</div>

<div class="card">
  <h2>Performance by stage</h2>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Stage</th><th>Avg wait (min)</th><th>Avg service (min)</th><th>Currently queued</th><th>Completed</th></tr></thead>
    <tbody>
    <?php foreach ($perStage as $r): $hot = $maxQ > 0 && (int)$r['in_queue'] === $maxQ; ?>
      <tr<?= $hot ? ' class="hotrow"' : '' ?>>
        <td><b><?= e($r['name']) ?></b><?= $hot ? ' <span class="badge b-waiting">Longest queue</span>' : '' ?></td>
        <td><?= e($r['avg_wait'] ?? '-') ?></td>
        <td><?= e($r['avg_service'] ?? '-') ?></td>
        <td>
          <?= (int)$r['in_queue'] ?>
          <div class="bar"><i style="width:<?= $maxQ ? round(100 * (int)$r['in_queue'] / $maxQ) : 0 ?>%"></i></div>
        </td>
        <td><?= (int)$r['done'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <p class="muted"><small>The highlighted stage has the most patients currently waiting, a likely bottleneck.</small></p>
</div>
<?php require '../includes/footer.php'; ?>