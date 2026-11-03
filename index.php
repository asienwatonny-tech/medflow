<?php
$title = 'Home';
require 'includes/header.php';
?>

<div class="hero">
  <img src="/medflow/assets/img/ambulance.svg" alt="">
  <div>
    <h1>Smarter patient flow,<br>better healthcare</h1>
    <p>Know where you are in your hospital visit and how long it will take.</p>
  </div>
</div>

<div class="card cta">
  <h2>Already registered?</h2>
  <p>Enter your Queue ID and the last 4 digits of your phone number to see your progress.</p>
  <a class="btn" href="/medflow/track.php">Track my visit</a>
  <a class="btn btn-alt" href="/medflow/login.php">Staff login</a>
</div>

<div class="grid">
  <div class="card">
    <h3>See your journey</h3>
    <p>Follow every stage from registration, triage, doctor and laboratory to results and pharmacy.</p>
  </div>
  <div class="card">
    <h3>Estimated waiting time</h3>
    <p>See how many patients are ahead of you and about how long you will wait.</p>
  </div>
  <div class="card">
    <h3>Live updates</h3>
    <p>Your page updates automatically as staff complete each stage, so you can plan your time.</p>
  </div>
</div>

<div class="card">
  <h2>How it works</h2>
  <ol class="how">
    <li>Register at reception and receive your Queue ID.</li>
    <li>Move through the stages while staff update your progress.</li>
    <li>Track your position and estimated remaining time on your phone.</li>
    <li>Collect your medication and complete your visit.</li>
  </ol>
</div>

<?php require 'includes/footer.php'; ?>