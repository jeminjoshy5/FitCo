<?php
session_start();
include("../../assets/db.php");
if(isset($_POST['sub']))
  {
    $email=$_POST['email'];
    $password=$_POST['pass'];
    $query = "SELECT * FROM gym WHERE gym_email='$email'";
    $result = mysqli_query($con, $query);
    if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);

    if ($_POST['pass'] === $user['gym_pass']) {

        if ($user['status'] === 'pending') {
            ?>
            <script src="/mini-projectTEMP/fitCo/assets/toast.js"></script>
            <script>
                  document.addEventListener('DOMContentLoaded', function () {
                    Toast.show("Your gym registration is still under review. You'll be able to log in once an admin approves it.", "info");
                  });
            </script>
            <?php
        } elseif ($user['status'] === 'rejected') {
            ?>
            <script src="/mini-projectTEMP/fitCo/assets/toast.js"></script>
            <script>
                  document.addEventListener('DOMContentLoaded', function () {
                    Toast.show("Your gym registration was not approved. Contact support for details.", "error");
                  });
            </script>
            <?php
        } elseif ($user['status'] === 'suspended') {
            ?>
            <script src="/mini-projectTEMP/fitCo/assets/toast.js"></script>
            <script>
                  document.addEventListener('DOMContentLoaded', function () {
                    Toast.show("Your gym account has been suspended. Contact support for details.", "error");
                  });
            </script>
            <?php
        } else {
        $_SESSION['login']=[
         'login_email' => $email,
          'login_pass' => $_POST['pass']
        ];

        ?>
        <script src="/mini-projectTEMP/fitCo/assets/toast.js"></script>
        <script>
              document.addEventListener('DOMContentLoaded', function () {
                Toast.show("Login successful.", "success");
                setTimeout(function () { window.location.href = '../../home/home.php'; }, 1100);
              });
        </script>
        <?php
        }

    } else {
       ?>
        <script src="/mini-projectTEMP/fitCo/assets/toast.js"></script>
        <script>
              document.addEventListener('DOMContentLoaded', function () {
                Toast.show("Incorrect password.", "error");
              });
        </script>
        <?php
    }
} else {
    ?>
        <script src="/mini-projectTEMP/fitCo/assets/toast.js"></script>
        <script>
              document.addEventListener('DOMContentLoaded', function () {
                Toast.show("No gym account found with that email.", "error");
              });
        </script>
        <?php
}

  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log In — FITCO</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="login.css">
</head>
<body>

  <div class="page">

    <a class="back-btn" href="/mini-projectTEMP/index.php" aria-label="Back to home">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
      <span>Back</span>
    </a>

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
        <h1 class="reveal">Run your gym<br>from one place.</h1>
        <p class="typewriter" id="brandSub" data-text="Members, schedules, and billing — all in a single dashboard built for studios and strength gyms."></p>
      </div>

      <p class="brand-foot"></p>
    </section>

    <section class="panel">
      <form class="login-form" action="login.php" method="post">
        <h2>Log in to your account</h2>
        <p class="sub">Welcome back. Enter your details to continue.</p>

        <table>
          <tr>
            <td><label for="email">Email</label></td>
          </tr>
          <tr>
            <td><input id="email" type="email" name="email" placeholder="you@yourgym.com" autocomplete="email" required></td>
          </tr>
          <tr>
            <td><label for="pass">Password</label></td>
          </tr>
          <tr>
            <td><input id="pass" type="password" name="pass" placeholder="Enter your password" autocomplete="current-password" required></td>
          </tr>
          <tr>
            <td>
              <div class="row-between">
                <a class="forgot" href="../forgot-pass/forgot-pass.php">Forgot password?</a>
              </div>
            </td>
          </tr>
          <tr>
            <td>
              <button type="submit" name="sub" value="Submit">Log in</button>
            </td>
          </tr>
        </table>

        <div class="divider">or</div>

        <a class="signup-link" href="../signup/signup.php">Don't have an account? <strong>Sign up</strong></a>
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

</body>
</html>