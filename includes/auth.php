<?php
session_start();
require_once __DIR__ . '/../config/db.php';

function require_role(array $roles) {
  if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], $roles)) {
    header('Location: /medflow/login.php'); exit;
  }
}
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }