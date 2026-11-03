<?php
function avg_minutes(PDO $db, int $stageId): float {
  $s = $db->prepare("SELECT AVG(TIMESTAMPDIFF(SECOND,started_at,completed_at))/60 FROM
    (SELECT started_at,completed_at FROM visit_stages
     WHERE stage_id=? AND status='completed' AND started_at IS NOT NULL
     ORDER BY completed_at DESC LIMIT 20) t");
  $s->execute([$stageId]);
  $a = $s->fetchColumn();
  if ($a === null || $a < 1) {
    $d = $db->prepare("SELECT default_minutes FROM stages WHERE id=?");
    $d->execute([$stageId]);
    return (float)$d->fetchColumn();
  }
  return (float)$a;
}

function journey(PDO $db, int $visitId): array {
  $s = $db->prepare("SELECT vs.*, st.name, st.seq FROM visit_stages vs
                     JOIN stages st ON st.id=vs.stage_id WHERE vs.visit_id=? ORDER BY st.seq");
  $s->execute([$visitId]);
  $rows = $s->fetchAll();
  $total = 0;
  foreach ($rows as &$r) {
    $avg = avg_minutes($db, (int)$r['stage_id']);
    $r['ahead'] = 0; $r['est'] = 0;
    if (in_array($r['status'], ['waiting','in_service'])) {
      $c = $db->prepare("SELECT COUNT(*) FROM visit_stages WHERE stage_id=? AND status IN ('waiting','in_service') AND queued_at < ?");
      $c->execute([$r['stage_id'], $r['queued_at']]);
      $r['ahead'] = (int)$c->fetchColumn();
      $r['est'] = (int)round(($r['ahead'] + 1) * $avg);
    } elseif ($r['status'] === 'pending') {
      $r['est'] = (int)round($avg);
    }
    $total += $r['est'];
  }
  return ['stages' => $rows, 'remaining' => $total];
}