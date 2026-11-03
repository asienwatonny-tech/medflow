<?php
require '../config/db.php';
require '../includes/estimate.php';
header('Content-Type: application/json');
$s = $db->prepare("SELECT v.id, p.phone FROM visits v JOIN patients p ON p.id=v.patient_id WHERE v.queue_code=?");
$s->execute([strtoupper(trim($_POST['code'] ?? ''))]);
$v = $s->fetch();
if (!$v || substr($v['phone'], -4) !== trim($_POST['pin'] ?? '')) {
  http_response_code(403); echo json_encode(['error' => 'denied']); exit;
}
$j = journey($db, (int)$v['id']);
$n = $db->prepare("SELECT message,created_at FROM notifications WHERE visit_id=? ORDER BY id DESC LIMIT 3");
$n->execute([$v['id']]);
echo json_encode(['stages' => $j['stages'], 'remaining' => $j['remaining'], 'notes' => $n->fetchAll()]);