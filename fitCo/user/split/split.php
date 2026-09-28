<?php
session_start();
include("../../assets/db.php");

if (!isset($_SESSION['user'])) {
    header("Location: ../../user-auth/login/login.php");
    exit();
}

$full_name = $_SESSION['user']['full_name'];
$first_name = explode(' ', trim($full_name))[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Build Your Split — FITCO</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="split.css">
</head>
<body>

  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <span class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M7 20 L7 4 L17 4"/>
            <path d="M7 12 L14 12"/>
          </svg>
        </span>
        <span class="brand-name">FITCO</span>
      </div>
      <a class="skip-link" href="../home/home.php">Skip for now &rarr;</a>
    </div>
  </header>

  <main class="page">

    <section class="intro reveal">
      <span class="step-tag">Step 2 of 2</span>
      <h1>Build your workout split, <?php echo htmlspecialchars($first_name); ?>.</h1>
      <p class="sub">Pick a split, choose exercises for each body part, and we'll show you exactly which muscles each day trains.</p>
    </section>

    <section class="split-card reveal" id="builder">

      <div class="field-block">
        <label for="splitType">Workout split</label>
        <select id="splitType">
          <option value="" disabled selected>Choose a split</option>
          <option value="ppl">Push / Pull / Legs</option>
          <option value="bro">Bro Split</option>
          <option value="custom">Custom</option>
        </select>
      </div>

      <div class="custom-picker" id="customPicker" hidden>
        <span class="field-label">Choose the body parts you want to train</span>
        <div class="checkbox-grid" id="customCheckboxes"></div>
      </div>

      <div id="sectionsContainer"></div>

      <div class="overview-panel" id="overviewPanel" hidden>
        <div class="overview-head">
          <h3>Split analysis</h3>
          <span class="overview-sub" id="overviewSub"></span>
        </div>
        <div class="overview-stats" id="overviewStats"></div>
        <div class="overview-muscles" id="overviewMuscles">
          <span class="distribution-label">Top muscles trained across this split</span>
          <div class="dist-bars" id="overviewBars"></div>
        </div>
      </div>

      <div class="save-row" id="saveRow" hidden>
        <p class="save-status" id="saveStatus"></p>
        <button id="saveSplitBtn" class="calc-btn">Save My Split</button>
      </div>

    </section>

    <section class="split-card success-card reveal" id="successCard" hidden>
      <div class="success-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
      </div>
      <h2>Your split is saved.</h2>
      <p class="sub" id="successSummary"></p>
      <a class="next-btn" href="../home/home.php">Go to Dashboard &rarr;</a>
    </section>

  </main>

  <script src="exercises-data.js"></script>
  <script src="split.js"></script>
</body>
</html>
