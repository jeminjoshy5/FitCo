<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Link Membership';
$activeNav = 'link';

// Already linked — nothing to do here.
if ($member) {
    header("Location: /mini-projectTEMP/fitCo/user/home/home.php");
    exit();
}

$gyms = [];
$r = mysqli_query($con, "SELECT gym_id, gym_name, gym_address FROM gym ORDER BY gym_name");
while ($row = mysqli_fetch_assoc($r)) { $gyms[] = $row; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_gym_id = (int) ($_POST['gym_id'] ?? 0);
    $email            = trim($_POST['email'] ?? '');

    if ($selected_gym_id <= 0) {
        $errors[] = "Please select your gym.";
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (!$errors) {
        $esc_email = esc($con, $email);
        $q = "SELECT member_id FROM members
              WHERE gym_id=$selected_gym_id AND email='$esc_email' AND user_id IS NULL
              LIMIT 1";
        $r = mysqli_query($con, $q);

        if ($r && mysqli_num_rows($r) > 0) {
            $row = mysqli_fetch_assoc($r);
            $found_member_id = (int) $row['member_id'];

            mysqli_query($con, "UPDATE members SET user_id=$user_id WHERE member_id=$found_member_id");

            flash_set('success', 'Your membership is now linked to your account.');
            header("Location: /mini-projectTEMP/fitCo/user/home/home.php");
            exit();
        } else {
            $errors[] = "We couldn't find an unlinked membership with that email at the selected gym. "
                      . "Make sure the email matches exactly what the gym has on file, or ask the gym "
                      . "to add/update your email — then try again.";
        }
    }
}

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Connect your gym membership</h2>
    <p class="section-sub">
      Your gym registers you as a member first (name, email, phone). Enter
      the same email they have on file, along with your gym, to connect it
      to this account.
    </p>
  </div>
</div>

<?php if ($errors): ?>
  <div class="flash flash--error reveal"><?php echo implode('<br>', array_map('h', $errors)); ?></div>
<?php endif; ?>

<form class="form-card reveal" method="post" style="animation-delay:0.05s; max-width:480px;">
  <div class="field">
    <label for="gym_id">Your gym *</label>
    <select class="input" id="gym_id" name="gym_id" required>
      <option value="">Select a gym…</option>
      <?php foreach ($gyms as $g): ?>
        <option value="<?php echo (int) $g['gym_id']; ?>" <?php echo (isset($_POST['gym_id']) && (int)$_POST['gym_id'] === (int)$g['gym_id']) ? 'selected' : ''; ?>>
          <?php echo h($g['gym_name']); ?> — <?php echo h($g['gym_address']); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="field">
    <label for="email">Email on file with the gym *</label>
    <input class="input" type="email" id="email" name="email" placeholder="you@example.com"
           value="<?php echo h($_POST['email'] ?? $user['email']); ?>" required>
  </div>

  <button type="submit" class="btn btn--primary">Link membership</button>
</form>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
