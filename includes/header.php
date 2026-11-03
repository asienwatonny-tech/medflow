<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$u = $_SESSION['user'] ?? null; $title = $title ?? 'MedFlow';
?>
<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title) ?> | MedFlow</title>
<link rel="icon" href="/medflow/assets/img/logo.svg">
<link rel="stylesheet" href="/medflow/assets/css/style.css"></head><body>
<header class="topbar">
  <a class="brand" href="/medflow/"><img src="/medflow/assets/img/logo.svg" alt="" width="36" height="36"><span>MedFlow</span></a>
  <nav>
  <?php if ($u): ?>
    <?php if ($u['role']==='admin'): ?><a href="/medflow/admin/reports.php">Reports</a><a href="/medflow/admin/staff.php">Staff</a>
    <?php elseif ($u['role']==='receptionist'): ?><a href="/medflow/staff/register.php">Register patient</a>
    <?php else: ?><a href="/medflow/staff/queue.php">My queue</a><?php endif; ?>
    <a href="/medflow/logout.php">Logout (<?= htmlspecialchars($u['name']) ?>)</a>
  <?php else: ?>
    <a href="/medflow/track.php">Track my visit</a><a href="/medflow/login.php">Staff login</a>
  <?php endif; ?>
  </nav>
</header>
<main class="container"></main>