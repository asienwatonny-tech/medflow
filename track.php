<?php
require 'config/db.php';
require 'includes/estimate.php';
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$v = null; $err = ''; $payload = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $s = $db->prepare("SELECT v.id, v.queue_code, p.full_name, p.phone FROM visits v JOIN patients p ON p.id=v.patient_id WHERE v.queue_code=?");
  $s->execute([strtoupper(trim($_POST['code'] ?? ''))]);
  $v = $s->fetch();
  if (!$v || substr($v['phone'], -4) !== trim($_POST['pin'] ?? '')) {
    $v = null; $err = 'Queue ID or phone digits not correct. Please check and try again.';
  } else {
    $j = journey($db, (int)$v['id']);
    $n = $db->prepare("SELECT message,created_at FROM notifications WHERE visit_id=? ORDER BY id DESC LIMIT 3");
    $n->execute([$v['id']]);
    $payload = ['stages' => $j['stages'], 'remaining' => $j['remaining'], 'notes' => $n->fetchAll()];
  }
}
$title = 'Track my visit';
require 'includes/header.php';

if (!$v): ?>
<div class="hero">
  <img src="/medflow/assets/img/ambulance.svg" alt="">
  <div>
    <h1>Smarter patient flow,<br>better healthcare</h1>
    <p>Know where you are in your hospital visit and how long it will take.</p>
  </div>
</div>

<div class="card" style="max-width:480px;margin:0 auto 18px">
  <h2>Track my visit</h2>
  <p class="muted">Enter the Queue ID you were given at reception.</p>
  <?php if ($err): ?><div class="msg err"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <label>Queue ID</label>
    <input name="code" placeholder="e.g. Q-0001" required value="<?= e($_POST['code'] ?? '') ?>">
    <label>Last 4 digits of your phone number</label>
    <input name="pin" maxlength="4" inputmode="numeric" placeholder="e.g. 5678" required>
    <button>Track my visit</button>
  </form>
</div>

<?php else: ?>
<div class="card" id="tracker" data-code="<?= e($v['queue_code']) ?>" data-pin="<?= e(substr($v['phone'], -4)) ?>">
  <div class="qhead">
    <div>
      <h2 style="margin-bottom:2px"><?= e($v['full_name']) ?></h2>
      <p class="muted">Queue ID <b><?= e($v['queue_code']) ?></b></p>
    </div>
    <a class="btn btn-alt" href="/medflow/track.php">Track another</a>
  </div>

  <div id="banner" class="status-banner">Loading your status...</div>

  <div class="stats">
    <div class="stat"><small>Current stage</small><b id="curStage">-</b></div>
    <div class="stat"><small>Patients ahead</small><b id="ahead">-</b></div>
    <div class="stat"><small>Wait at this stage</small><b id="wait">-</b></div>
    <div class="stat hot"><small>Total time left</small><b id="remaining">-</b></div>
  </div>

  <div class="steps" id="steps"></div>

  <table>
    <thead><tr><th>Stage</th><th>Status</th><th>Information</th></tr></thead>
    <tbody id="rows"></tbody>
  </table>

  <h3 style="margin-top:18px">Updates</h3>
  <div id="notes"><p class="muted">No updates yet.</p></div>

  <p class="muted" style="margin-top:14px"><small>Estimates are approximate, not guaranteed. <span id="updated"></span></small></p>
</div>

<script type="application/json" id="init"><?= json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="/medflow/assets/js/track.js"></script>
<?php endif;
require 'includes/footer.php'; ?>