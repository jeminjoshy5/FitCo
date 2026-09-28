<?php
session_start();
include("../../assets/db.php");

$error = "";

if (isset($_POST['sub'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['pass'];

    $email_esc = mysqli_real_escape_string($con, $email);
    $query  = "SELECT * FROM admins WHERE email='$email_esc'";
    $result = mysqli_query($con, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $admin = mysqli_fetch_assoc($result);

        if ($password === $admin['password']) {
            $_SESSION['admin'] = [
                'admin_id'  => $admin['admin_id'],
                'full_name' => $admin['full_name'],
                'email'     => $admin['email'],
            ];
            header("Location: ../../admin/home/home.php");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No admin account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Log In — FITCO</title>
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
        <h1 class="reveal">Platform control,<br>in one place.</h1>
        <p class="typewriter" id="brandSub" data-text="Review gym applications, manage accounts, and keep the platform healthy."></p>
      </div>

      <p class="brand-foot"></p>
    </section>

    <section class="panel">
      <form class="login-form" action="login.php" method="post" novalidate>
        <h2>Admin sign in</h2>
        <p class="sub">Platform administration — authorized staff only.</p>

        <?php if ($error): ?>
          <p class="notice error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <table>
          <tr>
            <td><label for="email">Email</label></td>
          </tr>
          <tr>
            <td><input id="email" type="email" name="email" placeholder="admin@fitco.com" autocomplete="email" required></td>
          </tr>
          <tr>
            <td><label for="pass">Password</label></td>
          </tr>
          <tr>
            <td><input id="pass" type="password" name="pass" placeholder="Enter your password" autocomplete="current-password" required></td>
          </tr>
          <tr>
            <td>
              <button type="submit" name="sub" value="Submit">Log in</button>
            </td>
          </tr>
        </table>
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

</body>
</html>
