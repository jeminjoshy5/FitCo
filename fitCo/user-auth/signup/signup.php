<?php
session_start();
include("../../assets/db.php");

$error = "";

if (isset($_POST['submit'])) {
    $full_name        = trim($_POST['full_name']);
    $email            = trim($_POST['email']);
    $mobile           = trim($_POST['mobile']);
    $address          = trim($_POST['address'] ?? '');
    $ec_name          = trim($_POST['emergency_contact_name'] ?? '');
    $ec_phone         = trim($_POST['emergency_contact_phone'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $email_esc = mysqli_real_escape_string($con, $email);
        $query  = "SELECT user_id FROM users WHERE email='$email_esc'";
        $result = mysqli_query($con, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $error = "An account with this email already exists.";
        } else {
            // Stage the signup — nothing is written to the `users` table
            // until the email is verified via OTP (see verify-otp.php).
            $_SESSION['signup'] = [
                'full_name'               => $full_name,
                'email'                   => $email,
                'mobile'                  => $mobile,
                'address'                 => $address,
                'emergency_contact_name'  => $ec_name,
                'emergency_contact_phone' => $ec_phone,
                'password'                => $password,
            ];
            // Clear any leftover OTP state from a previous/abandoned attempt
            unset($_SESSION['otp'], $_SESSION['otp_time'], $_SESSION['otp_attempts']);
            $_SESSION['otp_type'] = "signup";
            header("Location: ../verify-otp/verify-otp.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up — FITCO</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="signup.css">
</head>
<body>

  <div class="page">

    <section class="brand-side">
      <div class="brand">
        <span class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M7 20 L7 4 L17 4"/>
            <path d="M7 12 L14 12"/>
          </svg>
        </span>
        <span class="brand-name">FITCO</span>
      </div>

      <div class="brand-copy">
        <h1 class="reveal">Track your body.<br>Build your split.</h1>
        <p class="typewriter" id="brandSub" data-text="Log your BMI, plan your workout split, and see exactly which muscles you're training."></p>
      </div>

      <p class="brand-foot"></p>
    </section>

    <section class="panel">
      <form class="signup-form" action="" method="post" id="signupForm" novalidate>
        <h2>Create your account</h2>
        <p class="sub">Join FITCO to start tracking your progress.</p>

        <?php if ($error): ?>
          <p class="notice error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <table>

          <tr>
            <td><label for="full_name">Full Name</label></td>
          </tr>
          <tr>
            <td><input type="text" id="full_name" name="full_name" placeholder="Enter Your Name" required></td>
          </tr>

          <tr>
            <td><label for="email">Email ID</label></td>
          </tr>
          <tr>
            <td><input type="email" id="email" name="email" placeholder="you@example.com" required></td>
          </tr>

          <tr>
            <td><label for="mobile">Mobile Number</label></td>
          </tr>
          <tr>
            <td><input type="tel" id="mobile" name="mobile" placeholder="Enter Mobile Number" maxlength="15" required></td>
          </tr>

          <tr>
            <td><label for="address">Address <span class="field-optional">(optional)</span></label></td>
          </tr>
          <tr>
            <td><input type="text" id="address" name="address" placeholder="Street, city, state"></td>
          </tr>

          <tr>
            <td><label for="emergency_contact_name">Emergency contact name <span class="field-optional">(optional)</span></label></td>
          </tr>
          <tr>
            <td><input type="text" id="emergency_contact_name" name="emergency_contact_name" placeholder="Full name"></td>
          </tr>

          <tr>
            <td><label for="emergency_contact_phone">Emergency contact phone <span class="field-optional">(optional)</span></label></td>
          </tr>
          <tr>
            <td><input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" placeholder="Phone number" maxlength="15"></td>
          </tr>

          <tr>
            <td><label for="password">Password</label></td>
          </tr>
          <tr>
            <td>
              <div class="pass-field">
                <input type="password" id="password" name="password" placeholder="Create a Password" minlength="6" required>
              </div>
            </td>
          </tr>

          <tr>
            <td><label for="confirm_password">Confirm Password</label></td>
          </tr>
          <tr>
            <td>
              <div class="pass-field">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter Password" minlength="6" required>
              </div>
              <p class="field-hint" id="passHint"></p>
            </td>
          </tr>

          <tr>
            <td>
              <input type="hidden" name="submit" value="1">
              <button type="submit" id="registerBtn">
                <span class="btn-label">Create Account</span>
                <span class="btn-spinner" aria-hidden="true"></span>
              </button>
            </td>
          </tr>

        </table>

        <a class="signup-link" href="../login/login.php">Already have an account? <strong>Log in</strong></a>
      </form>
    </section>

  </div>

  <script>
    (function(){
      var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      var el = document.getElementById('brandSub');
      var text = el.getAttribute('data-text');

      if (reduce) {
        el.textContent = text;
        return;
      }

      el.classList.add('typing');
      var i = 0;
      function type(){
        el.textContent = text.slice(0, i);
        i++;
        if (i <= text.length) {
          setTimeout(type, 20);
        } else {
          el.classList.remove('typing');
        }
      }
      setTimeout(type, 650);
    })();
  </script>

  <script>
    (function(){
      var pass    = document.getElementById('password');
      var confirm = document.getElementById('confirm_password');
      var hint    = document.getElementById('passHint');

      function check(){
        if (!confirm.value) {
          hint.textContent = '';
          hint.classList.remove('is-error');
          return;
        }
        if (pass.value !== confirm.value) {
          hint.textContent = "Passwords don't match";
          hint.classList.add('is-error');
        } else {
          hint.textContent = '';
          hint.classList.remove('is-error');
        }
      }

      pass.addEventListener('input', check);
      confirm.addEventListener('input', check);
    })();
  </script>

  <script>
    (function(){
      var form = document.getElementById('signupForm');
      var btn  = document.getElementById('registerBtn');
      var submitting = false;

      form.addEventListener('submit', function(e){
        if (!form.checkValidity()) return;
        if (submitting) { e.preventDefault(); return; }
        submitting = true;
        btn.classList.add('is-loading');
      });

      window.addEventListener('pageshow', function(){
        submitting = false;
        btn.classList.remove('is-loading');
      });
    })();
  </script>

</body>
</html>
