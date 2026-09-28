<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Edit Profile';
$activeNav = 'profile';

$errors = [];

// ---- Save personal details ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_details'])) {
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $mobile     = trim($_POST['mobile'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $ec_name    = trim($_POST['emergency_contact_name'] ?? '');
    $ec_phone   = trim($_POST['emergency_contact_phone'] ?? '');

    if ($full_name === '') $errors[] = "Full name is required.";
    if ($mobile === '') $errors[] = "Mobile number is required.";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";

    // Email doubles as the login + gym-linking key — keep it unique.
    if (!$errors) {
        $email_esc = esc($con, $email);
        $r = mysqli_query($con, "SELECT user_id FROM users WHERE email='$email_esc' AND user_id != $user_id");
        if ($r && mysqli_num_rows($r) > 0) $errors[] = "That email is already used by another account.";
    }

    if (!$errors) {
        $fn = esc($con, $full_name);
        $em = esc($con, $email);
        $mb = esc($con, $mobile);
        $ad = esc($con, $address);
        $ecn = esc($con, $ec_name);
        $ecp = esc($con, $ec_phone);

        $sql = "UPDATE users SET full_name='$fn', email='$em', mobile='$mb', address='$ad',
                emergency_contact_name=" . ($ecn !== '' ? "'$ecn'" : "NULL") . ",
                emergency_contact_phone=" . ($ecp !== '' ? "'$ecp'" : "NULL") . "
                WHERE user_id=$user_id";

        if (mysqli_query($con, $sql)) {
            $_SESSION['user']['full_name'] = $full_name;
            $_SESSION['user']['email']     = $email;
            flash_set('success', 'Profile updated.');
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Update failed: " . mysqli_error($con);
        }
    }
}

// ---- Upload profile photo (separate form) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $finfo   = finfo_open(FILEINFO_MIME_TYPE);
        $mime    = finfo_file($finfo, $_FILES['photo']['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowed[$mime])) {
            flash_set('error', 'Please upload a JPG, PNG, or WEBP image.');
        } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            flash_set('error', 'Image must be smaller than 2MB.');
        } else {
            $destDir = __DIR__ . "/../../assets/uploads/avatars";
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);

            $newName = 'user_' . $user_id . '_' . time() . '.' . $allowed[$mime];
            if (move_uploaded_file($_FILES['photo']['tmp_name'], "$destDir/$newName")) {
                // Clean up the old photo file, if any.
                if (!empty($user['profile_photo'])) {
                    $old = $destDir . '/' . $user['profile_photo'];
                    if (is_file($old)) @unlink($old);
                }
                $nn = esc($con, $newName);
                mysqli_query($con, "UPDATE users SET profile_photo='$nn' WHERE user_id=$user_id");
                flash_set('success', 'Profile photo updated.');
            } else {
                flash_set('error', 'Could not save the uploaded photo.');
            }
        }
    } else {
        flash_set('error', 'Please choose an image to upload.');
    }
    header("Location: edit.php");
    exit();
}

// ---- Change password (separate form) ----
$pwErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($current !== $user['password']) {
        $pwErrors[] = "Current password is incorrect.";
    }
    if (strlen($new) < 6) {
        $pwErrors[] = "New password must be at least 6 characters.";
    }
    if ($new !== $confirm) {
        $pwErrors[] = "New password and confirmation don't match.";
    }

    if (!$pwErrors) {
        $new_esc = mysqli_real_escape_string($con, $new);
        mysqli_query($con, "UPDATE users SET password='$new_esc' WHERE user_id=$user_id");
        flash_set('success', 'Password changed.');
        header("Location: edit.php");
        exit();
    }
}

// ---- Deactivate account (separate form) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate'])) {
    mysqli_query($con, "UPDATE users SET status='inactive' WHERE user_id=$user_id");
    session_unset();
    session_destroy();
    header("Location: /mini-projectTEMP/fitCo/user-auth/login/login.php?deactivated=1");
    exit();
}

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Edit profile</h2>
    <p class="section-sub">Update your details, photo, and account settings.</p>
  </div>
  <a class="btn" href="index.php">← Back to profile</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash--error reveal"><?php echo implode('<br>', array_map('h', $errors)); ?></div>
<?php endif; ?>

<section class="panels">

  <div class="panel-card reveal" style="animation-delay:0.05s">
    <div class="panel-head"><h2>Personal information</h2></div>
    <form class="form-card" method="post">
      <div class="field-row">
        <div class="field">
          <label for="full_name">Full name *</label>
          <input class="input" type="text" id="full_name" name="full_name" value="<?php echo h($user['full_name']); ?>" required>
        </div>
        <div class="field">
          <label for="mobile">Mobile *</label>
          <input class="input" type="text" id="mobile" name="mobile" value="<?php echo h($user['mobile']); ?>" required>
        </div>
      </div>
      <div class="field">
        <label for="email">Email *</label>
        <input class="input" type="email" id="email" name="email" value="<?php echo h($user['email']); ?>" required>
      </div>
      <div class="field">
        <label for="address">Address</label>
        <textarea class="input" id="address" name="address" rows="2"><?php echo h($user['address'] ?? ''); ?></textarea>
      </div>
      <div class="field-row">
        <div class="field">
          <label for="emergency_contact_name">Emergency contact name</label>
          <input class="input" type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo h($user['emergency_contact_name'] ?? ''); ?>">
        </div>
        <div class="field">
          <label for="emergency_contact_phone">Emergency contact phone</label>
          <input class="input" type="text" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo h($user['emergency_contact_phone'] ?? ''); ?>">
        </div>
      </div>
      <button type="submit" name="save_details" value="1" class="btn btn--primary">Save changes</button>
    </form>
  </div>

  <div class="panel-card reveal" style="animation-delay:0.1s">
    <div class="panel-head"><h2>Profile photo</h2></div>
    <form class="form-card" method="post" enctype="multipart/form-data">
      <div class="field">
        <label for="photo">Upload a new photo (JPG/PNG/WEBP, max 2MB)</label>
        <input class="input" type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png,.webp" required>
      </div>
      <button type="submit" name="upload_photo" value="1" class="btn btn--primary">Upload</button>
    </form>
  </div>

  <div class="panel-card reveal" style="animation-delay:0.15s">
    <div class="panel-head"><h2>Change password</h2></div>
    <?php if (!empty($pwErrors)): ?>
      <div class="flash flash--error"><?php echo implode('<br>', array_map('h', $pwErrors)); ?></div>
    <?php endif; ?>
    <form class="form-card" method="post">
      <div class="field">
        <label for="current_password">Current password</label>
        <input class="input" type="password" id="current_password" name="current_password" required>
      </div>
      <div class="field-row">
        <div class="field">
          <label for="new_password">New password</label>
          <input class="input" type="password" id="new_password" name="new_password" minlength="6" required>
        </div>
        <div class="field">
          <label for="confirm_password">Confirm new password</label>
          <input class="input" type="password" id="confirm_password" name="confirm_password" minlength="6" required>
        </div>
      </div>
      <button type="submit" name="change_password" value="1" class="btn btn--primary">Change password</button>
    </form>
  </div>

  <div class="panel-card reveal" style="animation-delay:0.2s">
    <div class="panel-head"><h2>Account</h2></div>
    <p class="section-sub" style="margin-bottom:14px;">
      Deactivating logs you out and hides your account. Your gym membership
      history is kept, and you can ask your gym or support to reactivate it.
    </p>
    <form method="post" onsubmit="return confirm('Deactivate your account? You will be logged out immediately.');">
      <button type="submit" name="deactivate" value="1" class="btn btn--danger">Deactivate account</button>
    </form>
  </div>

</section>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
