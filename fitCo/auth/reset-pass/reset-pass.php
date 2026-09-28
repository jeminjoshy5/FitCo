<?php
session_start();
include("../../assets/db.php");

if (!isset($_SESSION['forgot_email'])) {
    header("Location: ../forgot-password/forgot-password.php");
    exit();
}

$email = $_SESSION['forgot_email'];
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'], $_POST['confirm_password'])) {
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
$hs = mysqli_real_escape_string($con, $password);
$email_esc = mysqli_real_escape_string($con, $email);

        $query = "UPDATE gym SET gym_pass='$hs' WHERE gym_email='$email_esc'";

        if (mysqli_query($con, $query)) {
            // Clean up session data used only for the forgot-password/OTP flow
            unset($_SESSION['forgot_email'], $_SESSION['otp_type'], $_SESSION['otp'], $_SESSION['otp_time'], $_SESSION['otp_attempts']);
?>
<script src="/mini-projectTEMP/fitCo/assets/toast.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    Toast.show("Password reset successful.", "success");
    setTimeout(function () { window.location.href = '../login/login.php'; }, 1400);
  });
</script>
<?php
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password — FITCO</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="reset-pass.css">
</head>
<body>

  <main class="page">

    <div class="brand">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M7 20 L7 4 L17 4"/>
          <path d="M7 12 L14 12"/>
        </svg>
      </span>
      <span class="brand-name">FITCO</span>
    </div>

    <section class="auth-card">
      <h1>Reset your password</h1>
      <p class="sub">Choose a new password for <strong><?php echo htmlspecialchars($email); ?></strong></p>

      <?php if ($error): ?>
        <p class="notice error"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <form class="auth-form" method="post" id="resetForm" novalidate>

        <label class="field-label" for="password">New password</label>
        <div class="pass-field">
          <input
            class="field-input"
            type="password"
            name="password"
            id="password"
            placeholder="••••••••"
            autocomplete="new-password"
            minlength="6"
            required>
          <button type="button" class="pass-toggle" data-target="password" aria-label="Show password">
            <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1.5 12s4-7.5 10.5-7.5S22.5 12 22.5 12s-4 7.5-10.5 7.5S1.5 12 1.5 12z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
              <path d="M3 3l18 18"/>
              <path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c6.5 0 10.5 7 10.5 7a17.9 17.9 0 0 1-3.6 4.6M6.6 6.6C3.7 8.4 1.5 12 1.5 12s4 7 10.5 7c1.4 0 2.7-.3 3.9-.8"/>
              <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
            </svg>
          </button>
        </div>
        <p class="field-hint" id="lengthHint">At least 6 characters</p>

        <label class="field-label" for="confirm_password">Confirm password</label>
        <div class="pass-field">
          <input
            class="field-input"
            type="password"
            name="confirm_password"
            id="confirm_password"
            placeholder="••••••••"
            autocomplete="new-password"
            minlength="6"
            required>
          <button type="button" class="pass-toggle" data-target="confirm_password" aria-label="Show password">
            <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1.5 12s4-7.5 10.5-7.5S22.5 12 22.5 12s-4 7.5-10.5 7.5S1.5 12 1.5 12z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
              <path d="M3 3l18 18"/>
              <path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c6.5 0 10.5 7 10.5 7a17.9 17.9 0 0 1-3.6 4.6M6.6 6.6C3.7 8.4 1.5 12 1.5 12s4 7 10.5 7c1.4 0 2.7-.3 3.9-.8"/>
              <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
            </svg>
          </button>
        </div>
        <p class="field-hint" id="matchHint">&nbsp;</p>

        <button type="submit" class="submit-btn">
          <span class="btn-label">Reset password</span>
          <span class="btn-spinner" aria-hidden="true"></span>
        </button>
      </form>

      <a class="back-link" href="../login/login.php">&larr; Back to login</a>
    </section>

  </main>

  <script>
    // ---- Show/hide password toggles ----
    (function(){
      var toggles = Array.prototype.slice.call(document.querySelectorAll('.pass-toggle'));

      toggles.forEach(function(btn){
        var input = document.getElementById(btn.getAttribute('data-target'));
        var eye = btn.querySelector('.icon-eye');
        var eyeOff = btn.querySelector('.icon-eye-off');

        btn.addEventListener('click', function(){
          var showing = input.type === 'text';
          input.type = showing ? 'password' : 'text';
          btn.classList.toggle('is-visible', !showing);
          btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
          eye.style.display = showing ? '' : 'none';
          eyeOff.style.display = showing ? 'none' : '';
        });
      });
    })();

    // ---- Inline validation: length + match, checked as the user types ----
    (function(){
      var password = document.getElementById('password');
      var confirm = document.getElementById('confirm_password');
      var lengthHint = document.getElementById('lengthHint');
      var matchHint = document.getElementById('matchHint');
      var form = document.getElementById('resetForm');

      function checkLength(){
        var ok = password.value.length === 0 || password.value.length >= 6;
        lengthHint.textContent = 'At least 6 characters';
        lengthHint.classList.toggle('is-error', !ok);
        return password.value.length >= 6;
      }

      function checkMatch(){
        if (!confirm.value) {
          matchHint.textContent = '\u00A0';
          matchHint.classList.remove('is-error');
          return true;
        }
        var ok = password.value === confirm.value;
        matchHint.textContent = ok ? 'Passwords match' : 'Passwords do not match';
        matchHint.classList.toggle('is-error', !ok);
        return ok;
      }

      password.addEventListener('input', function(){ checkLength(); checkMatch(); });
      confirm.addEventListener('input', checkMatch);

      form.addEventListener('submit', function(e){
        var lengthOk = checkLength();
        var matchOk = checkMatch();
        if (!lengthOk || !matchOk || !form.checkValidity()) {
          e.preventDefault();
          return;
        }
        form.querySelector('.submit-btn').classList.add('is-loading');
      });
    })();
  </script>

</body>
</html>