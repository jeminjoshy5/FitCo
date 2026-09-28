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
<title>Your BMI — FITCO</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="bmi.css">
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
      <span class="step-tag">Step 1 of 2</span>
      <h1>Welcome, <?php echo htmlspecialchars($first_name); ?>. Let's log your BMI.</h1>
      <p class="sub">Move the sliders or type your numbers in — we'll do the rest.</p>
    </section>

    <section class="bmi-card reveal">

      <div class="measure-grid">

        <!-- HEIGHT -->
        <div class="measure">
          <div class="measure-visual">
            <div class="ruler" id="rulerTrack">
              <div class="ruler-ticks" id="rulerTicks"></div>
              <div class="ruler-marker" id="rulerMarker">
                <span class="ruler-marker-line"></span>
              </div>
            </div>
          </div>
          <div class="measure-field">
            <label for="height">Height</label>
            <div class="input-row">
              <input type="number" id="height" inputmode="decimal" min="100" max="220" step="0.5" placeholder="0">
              <span class="unit">cm</span>
            </div>
            <input type="range" id="heightRange" class="range-input" min="100" max="220" step="0.5" value="170">
          </div>
        </div>

        <!-- WEIGHT -->
        <div class="measure">
          <div class="measure-visual">
            <svg class="dial" viewBox="0 0 220 132" aria-hidden="true">
              <path class="dial-track" d="M 14 118 A 96 96 0 0 1 206 118"></path>
              <g id="dialTicks"></g>
              <g id="dialNeedle" style="transform-origin:110px 118px;">
                <line x1="110" y1="118" x2="110" y2="34" class="needle-line"></line>
                <circle cx="110" cy="118" r="7" class="needle-hub"></circle>
              </g>
            </svg>
            <div class="dial-readout"><span id="weightReadout">0.0</span><span class="dial-readout-unit">kg</span></div>
          </div>
          <div class="measure-field">
            <label for="weight">Weight</label>
            <div class="input-row">
              <input type="number" id="weight" inputmode="decimal" min="20" max="200" step="0.1" placeholder="0">
              <span class="unit">kg</span>
            </div>
            <input type="range" id="weightRange" class="range-input" min="20" max="200" step="0.5" value="65">
          </div>
        </div>

      </div>

      <button id="calcBtn" class="calc-btn" disabled>Calculate BMI</button>

      <div class="result" id="resultSection" hidden>

        <div class="result-top">
          <div class="result-value">
            <span class="result-number" id="bmiValue">0.0</span>
            <span class="result-cat" id="bmiCategory">—</span>
          </div>
          <p class="result-desc" id="bmiDesc"></p>
        </div>

        <div class="bmi-range-bar">
          <div class="bmi-range-bar-marker" id="bmiRangeMarker"></div>
        </div>
        <div class="bmi-range-labels">
          <span>Underweight</span>
          <span>Normal</span>
          <span>Overweight</span>
          <span>Obese</span>
        </div>

        <p class="save-status" id="saveStatus"></p>

        <button id="nextBtn" class="next-btn" hidden>Next: Build Your Split &rarr;</button>
      </div>

      <p class="fine-print">BMI is a general screening measure based on height and weight — it doesn't account for muscle mass, frame, or body composition.</p>

    </section>

  </main>

  <script src="bmi.js"></script>
</body>
</html>
