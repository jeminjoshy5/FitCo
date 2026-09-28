<?php
session_start();
include("../../assets/db.php");

// PHPMailer (installed via Composer: composer require phpmailer/phpmailer)
require '../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
if (isset($_SESSION['otp_type']) && $_SESSION['otp_type'] == "signup") {

    $email = $_SESSION['signup']['email'];

}
elseif (isset($_SESSION['otp_type']) && $_SESSION['otp_type'] == "forgot_password") {

    $email = $_SESSION['forgot_email'];

}
else{

    header("Location: ../login/login.php");
    exit();
}

$message = "";
$error   = "";

// ---------- Helper: generate + send a fresh OTP ----------
function sendOtp($email)
{
    $otp = strval(random_int(100000, 999999)); // 6-digit OTP

    $_SESSION['otp']         = $otp;
    $_SESSION['otp_time']    = time();       // used for expiry check
    $_SESSION['otp_attempts'] = 0;           // reset failed-attempt counter

    $mail = new PHPMailer(true);

    try {
        // Server settings — replace with your actual SMTP credentials
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'fitnwellnesscentre@gmail.com';      
        $mail->Password   = 'tvyu ozff aqeu ttpe';      
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('fitnwellnesscentre@gmail.com', 'FITCO');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your FITCO Verification Code';
        $mail->Body    = "Your OTP code is: <b>$otp</b><br>This code expires in 5 minutes.";
        $mail->AltBody = "Your OTP code is: $otp (expires in 5 minutes)";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Mailer Error: " . $mail->ErrorInfo;
    }
}

// ---------- 1. SEND OTP (first load, or resend requested) ----------
if (!isset($_SESSION['otp']) || isset($_POST['resend'])) {
    $sendResult = sendOtp($email);
    if ($sendResult === true) {
        $message = "An OTP has been sent to " . htmlspecialchars($email);
    } else {
        $error = $sendResult;
    }
}

// ---------- 2. VERIFY OTP ----------
if (isset($_POST['submit']) && !isset($_POST['resend'])) {
    $entered_otp = trim($_POST['otp'] ?? '');
    $expired = (time() - ($_SESSION['otp_time'] ?? 0)) > 300;

    if ($expired) {
        $error = "OTP expired. Please request a new one.";
    } elseif (empty($entered_otp)) {
        $error = "Please enter the OTP.";
    } elseif ($entered_otp !== $_SESSION['otp']) {
        $_SESSION['otp_attempts'] = ($_SESSION['otp_attempts'] ?? 0) + 1;
        if ($_SESSION['otp_attempts'] >= 5) {
            $error = "Too many incorrect attempts. Please request a new OTP.";
            unset($_SESSION['otp']);
        } else {
            $error = "Incorrect OTP. Please try again.";
        }
    } else {

//signup

    if($_SESSION['otp_type']=="signup")
  {
    $data = $_SESSION['signup'];

    $gym_name    = mysqli_real_escape_string($con, $data['gym_name']);
    $owner_name  = mysqli_real_escape_string($con, $data['owner_name']);
    $gym_address = mysqli_real_escape_string($con, $data['gym_address']);
    $email       = mysqli_real_escape_string($con, $data['email']);
    $mobile      = mysqli_real_escape_string($con, $data['mobile']);
    $alt_mobile  = mysqli_real_escape_string($con, $data['alt_mobile']);
    $gym_license= mysqli_real_escape_string($con, $data['gym_license']);
    $pass=mysqli_real_escape_string($con,$data['password']);
    $insertSql = "INSERT INTO gym (gym_name, owner_name,gym_email, gym_address,gym_mno,gym_alt_mno,gym_license,gym_pass)
                  VALUES ('$gym_name', '$owner_name', '$email', '$gym_address', '$mobile', '$alt_mobile','$gym_license','$pass')";

    if (mysqli_query($con, $insertSql)) {
        // Clean up session data used only for signup/OTP
unset(
    $_SESSION['signup'],
    $_SESSION['otp'],
    $_SESSION['otp_time'],
    $_SESSION['otp_attempts'],
    $_SESSION['otp_type']
);
?>
<script src="/mini-projectTEMP/fitCo/assets/toast.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    Toast.show("Account created.", "success");
    setTimeout(function () { window.location.href = '../login/login.php'; }, 1400);
  });
</script>
<?php
    } else {
        $error = "Registration failed: " . mysqli_error($con);
    }
  }
  else if($_SESSION['otp_type']=="forgot_password")
    {
        $_SESSION['reset_allowed'] = true;

    unset($_SESSION['otp']);
    unset($_SESSION['otp_time']);
    unset($_SESSION['otp_attempts']);
    unset($_SESSION['otp_type']);

    header("Location: ../reset-pass/reset-pass.php");
    exit();
    }
    
}
}

// ---------- Seconds left on the current code, for the countdown ring ----------
$secondsLeft = max(0, 300 - (time() - ($_SESSION['otp_time'] ?? time())));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify OTP — FITCO</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="verify-otp.css">
</head>
<body>

  <main class="page">
<!-- 
    <div class="brand">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M7 20 L7 4 L17 4"/>
          <path d="M7 12 L14 12"/>
        </svg>
      </span>
      <span class="brand-name">FITCO</span>
    </div> -->

    <section class="otp-card">
      <h1>Verify your email</h1>
      <p class="sub">Enter the 6-digit code sent to <strong><?php echo htmlspecialchars($email); ?></strong></p>

      <?php if ($message): ?>
        <p class="notice success"><?php echo htmlspecialchars($message); ?></p>
      <?php endif; ?>

      <?php if ($error): ?>
        <p class="notice error"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <form class="otp-form" method="post" id="otpForm" novalidate>
        <div class="otp-boxes" id="otpBoxes">
          <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code">
          <input class="otp-box" type="text" inputmode="numeric" maxlength="1">
          <input class="otp-box" type="text" inputmode="numeric" maxlength="1">
          <input class="otp-box" type="text" inputmode="numeric" maxlength="1">
          <input class="otp-box" type="text" inputmode="numeric" maxlength="1">
          <input class="otp-box" type="text" inputmode="numeric" maxlength="1">
        </div>
        <input type="hidden" name="otp" id="otpValue">

        <div class="countdown-row" id="countdownRow">
          <svg class="countdown-ring" viewBox="0 0 26 26">
            <circle class="track" cx="13" cy="13" r="10.5"></circle>
            <circle class="progress" id="countdownProgress" cx="13" cy="13" r="10.5"></circle>
          </svg>
          <span class="countdown-text" id="countdownText">
            Code expires in <span class="time" id="countdownTime">5:00</span>
          </span>
        </div>

        <button type="submit" name="submit" class="verify-btn">Verify code</button>
      </form>

      <form class="resend-row" method="post">
        <span>Didn't get a code?</span>
        <button type="submit" name="resend" class="resend-link">Resend</button>
      </form>

      <a class="back-link" href="../signup/signup.php">&larr; Back to sign up</a>
    </section>

  </main>

  <script>
    // ---- Segmented OTP input ----
    (function(){
      var boxes = Array.prototype.slice.call(document.querySelectorAll('.otp-box'));
      var hidden = document.getElementById('otpValue');
      var form = document.getElementById('otpForm');

      function sync(){
        hidden.value = boxes.map(function(b){ return b.value; }).join('');
      }

      function markFilled(box, isFilled){
        // Force the fill-pop animation to restart even if the box was
        // already filled (e.g. typing over an existing digit) — remove
        // the class, force reflow, then re-add it.
        box.classList.remove('filled');
        if (isFilled) {
          void box.offsetWidth;
          box.classList.add('filled');
        }
      }

      boxes.forEach(function(box, idx){
        box.addEventListener('input', function(){
          box.value = box.value.replace(/[^0-9]/g, '').slice(0, 1);
          markFilled(box, box.value.length === 1);
          if (box.value && idx < boxes.length - 1) {
            boxes[idx + 1].focus();
          }
          sync();
        });

        box.addEventListener('keydown', function(e){
          if (e.key === 'Backspace' && !box.value && idx > 0) {
            boxes[idx - 1].focus();
          }
        });

        box.addEventListener('paste', function(e){
          e.preventDefault();
          var digits = (e.clipboardData.getData('text') || '').replace(/[^0-9]/g, '').split('');
          boxes.forEach(function(b, i){
            b.value = digits[i] || '';
            markFilled(b, !!digits[i]);
          });
          sync();
          var next = boxes[Math.min(digits.length, boxes.length - 1)];
          if (next) next.focus();
        });
      });

      form.addEventListener('submit', function(e){
        sync();
        if (hidden.value.length !== 6) {
          e.preventDefault();
          boxes.forEach(function(b){
            if (!b.value) {
              b.classList.add('shake');
              setTimeout(function(){ b.classList.remove('shake'); }, 320);
            }
          });
          boxes[0].focus();
        }
      });

      if (boxes[0]) boxes[0].focus();
    })();

    // ---- Countdown ring, seeded from the server-side otp_time ----
    (function(){
      var totalSeconds = 300;
      var secondsLeft = <?php echo (int) $secondsLeft; ?>;
      var ring = document.getElementById('countdownProgress');
      var timeEl = document.getElementById('countdownTime');
      var row = document.getElementById('countdownRow');
      var radius = 10.5;
      var circumference = 2 * Math.PI * radius;

      ring.style.strokeDasharray = circumference;

      function render(){
        var m = Math.floor(secondsLeft / 60);
        var s = secondsLeft % 60;
        timeEl.textContent = m + ':' + (s < 10 ? '0' : '') + s;

        var fraction = secondsLeft / totalSeconds;
        ring.style.strokeDashoffset = circumference * (1 - fraction);

        if (secondsLeft <= 0) {
          row.classList.add('expired');
          timeEl.parentElement.firstChild.textContent = 'Code expired — ';
        }
      }

      render();

      var timer = setInterval(function(){
        secondsLeft = Math.max(0, secondsLeft - 1);
        render();
        if (secondsLeft <= 0) clearInterval(timer);
      }, 1000);
    })();
  </script>

</body>
</html>