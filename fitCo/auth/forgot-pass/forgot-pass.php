<?php
session_start();
include("../../assets/db.php");

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email=$_POST['email'];
    $query  = "SELECT * FROM gym WHERE gym_email='$email'";
    $result = mysqli_query($con, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $_SESSION['forgot_email'] = $email;
        $_SESSION['otp_type']     = "forgot_password";
        header("Location: ../verify-otp/verify-otp.php");
        exit();
    } else {
        $error = "Email not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — FITCO</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="forgot-pass.css">
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
      <h1>Forgot your password?</h1>
      <p class="sub">Enter the email linked to your gym account and we'll send a verification code to reset it.</p>

      <?php if ($error): ?>
        <p class="notice error"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <form class="auth-form" method="post" id="forgotForm" novalidate>
        <label class="field-label" for="email">Email address</label>
        <input
          class="field-input"
          type="email"
          name="email"
          id="email"
          placeholder="you@gym.com"
          autocomplete="email"
          required>

        <button type="submit" class="submit-btn">
          <span class="btn-label">Send code</span>
          <span class="btn-spinner" aria-hidden="true"></span>
        </button>
      </form>

      <a class="back-link" href="../login/login.php">&larr; Back to login</a>
    </section>

  </main>

  <script>
    // Instant loading feedback the moment the form is submitted — the
    // request itself may take a moment (DB lookup + possible redirect),
    // so the button should say so right away rather than sitting idle.
    (function(){
      var form = document.getElementById('forgotForm');
      var btn = form.querySelector('.submit-btn');

      form.addEventListener('submit', function(e){
        if (!form.checkValidity()) return; // let native validation show first
        btn.classList.add('is-loading');
      });
    })();
  </script>

</body>
</html>