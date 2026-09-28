<?php
session_start();
include("../../assets/db.php");

if(isset($_POST['submit']))
{
    $gym_name         = $_POST['gym_name'];
    $owner_name       = $_POST['owner_name'];
    $gym_address      = $_POST['gym_address'];
    $email            = $_POST['email'];
    $mobile           = $_POST['mobile'];
    $alt_mobile       = $_POST['alt_mobile'];
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
$gym_license = '';
$licenseError = '';
if (isset($_FILES['gym_license']) && $_FILES['gym_license']['error'] === UPLOAD_ERR_OK) {
    $allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($_FILES['gym_license']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        $licenseError = "License must be a PDF, JPG, or PNG file.";
    } else {
        $uploadDir = __DIR__ . '/../../assets/uploads/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
        $storedName = 'license_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (move_uploaded_file($_FILES['gym_license']['tmp_name'], $uploadDir . $storedName)) {
            $gym_license = $storedName;
        } else {
            $licenseError = "Could not save the uploaded license. Please try again.";
        }
    }
} else {
    $licenseError = "Please upload your gym license.";
}
    if($licenseError)
    {
      ?>
      <script src="/mini-projectTEMP/fitCo/assets/toast.js"></script>
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          Toast.show(<?php echo json_encode($licenseError); ?>, "error");
        });
      </script>
      <?php
    }
    elseif(strlen($password) < 6)
    {
      ?>
      <script src="/mini-projectTEMP/fitCo/assets/toast.js"></script>
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          Toast.show("Password must be at least 8 characters long.", "error");
        });
      </script>
      <?php
    }
    elseif($password !== $confirm_password)
    {
      ?>
      <script src="/mini-projectTEMP/fitCo/assets/toast.js"></script>
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          Toast.show("Passwords do not match.", "error");
        });
      </script>
      <?php
    }
    else
    {
    // Check if email already exists
    $query = "SELECT * FROM gym WHERE gym_email='$email'";
    $result = mysqli_query($con, $query);

    if(mysqli_num_rows($result) > 0)
    {
      ?>
      <script src="/mini-projectTEMP/fitCo/assets/toast.js"></script>
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          Toast.show("Email already exists.", "error");
        });
      </script>
      <?php
    }
    else
    {
        $_SESSION['signup'] = [
            'gym_name'    => $gym_name,
            'owner_name'  => $owner_name,
            'gym_address' => $gym_address,
            'email'       => $email,
            'mobile'      => $mobile,
            'alt_mobile'  => $alt_mobile,
            'gym_license' =>$gym_license,
            'password'    => $password
        ];
          // Clear any leftover OTP state from a previous/abandoned signup attempt
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
        <h1 class="reveal">List your gym<br>in one place.</h1>
        <p class="typewriter" id="brandSub" data-text="Verify your license, manage bookings, and start taking members — all from a single dashboard."></p>
      </div>

      <p class="brand-foot"></p>
    </section>

    <section class="panel">
      <form class="signup-form" action="" method="post" enctype="multipart/form-data" id="signupForm">
        <h2>Create your gym account</h2>
        <p class="sub">Tell us about your gym to get started.</p>

        <table>

          <tr>
            <td><label for="gym_name">Gym Name</label></td>
          </tr>
          <tr>
            <td><input type="text" id="gym_name" name="gym_name" placeholder="Enter Gym Name" required></td>
          </tr>

          <tr>
            <td><label for="owner_name">Owner Name</label></td>
          </tr>
          <tr>
            <td><input type="text" id="owner_name" name="owner_name" placeholder="Enter Owner Name" required></td>
          </tr>

          <tr>
            <td><label for="email">Email ID</label></td>
          </tr>
          <tr>
            <td><input type="email" id="email" name="email" placeholder="Enter Your Email" required></td>
          </tr>

          <tr>
            <td><label for="gym_address">Gym Address</label></td>
          </tr>
          <tr>
            <td><textarea id="gym_address" name="gym_address" rows="2" cols="30" placeholder="Enter Gym Address" required></textarea></td>
          </tr>

          <tr>
            <td><label for="mobile">Mobile Number</label></td>
          </tr>
          <tr>
            <td><input type="tel" id="mobile" name="mobile" placeholder="Enter Mobile Number" maxlength="15" required></td>
          </tr>

          <tr>
            <td><label for="alt_mobile">Alternate Mobile Number</label></td>
          </tr>
          <tr>
            <td><input type="tel" id="alt_mobile" name="alt_mobile" placeholder="Enter Alternate Mobile Number" maxlength="15"></td>
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
            <td><label for="license">Gym License</label></td>
          </tr>
          <tr>
            <td><input type="file" id="license" name="gym_license" accept=".pdf,.jpg,.jpeg,.png" required></td>
          </tr>

          <tr>
            <td>
              <input type="hidden" name="submit" value="1">
              <button type="submit" id="registerBtn">
                <span class="btn-label">Register Gym</span>
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
      setTimeout(type, 650); // start once the header has settled in
    })();
  </script>

  <script>
    // Show/hide password toggles
    (function(){
      var toggles = document.querySelectorAll('.pass-toggle');
      toggles.forEach(function(btn){
        btn.addEventListener('click', function(){
          var input = document.getElementById(btn.getAttribute('data-target'));
          var showing = input.type === 'text';
          input.type = showing ? 'password' : 'text';
          btn.classList.toggle('is-visible', !showing);
          btn.setAttribute('aria-pressed', String(!showing));
          btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        });
      });
    })();

    // Live "passwords match" feedback on the confirm field
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
    // Loading state on the submit button — stays until the browser
    // navigates away to verify-otp.php (or the page reloads with an error).
    (function(){
      var form = document.getElementById('signupForm');
      var btn  = document.getElementById('registerBtn');
      var submitting = false;

      form.addEventListener('submit', function(e){
        // Let native validation (required fields etc.) show its own UI first.
        if (!form.checkValidity()) return;

        if (submitting) {
          e.preventDefault();
          return;
        }

        submitting = true;
        btn.classList.add('is-loading');
        // Not using the `disabled` attribute here: a disabled submit button
        // can be dropped from the form data in some browsers, and we rely
        // on the hidden "submit" field instead, so pointer-events in CSS
        // handles blocking a second click.
      });

      // If the page is restored from bfcache (e.g. user hits back after
      // a server-side validation error), reset the button.
      window.addEventListener('pageshow', function(){
        submitting = false;
        btn.classList.remove('is-loading');
      });
    })();
  </script>

</body>
</html>