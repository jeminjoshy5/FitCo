<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Edit Member';
$activeNav = 'members';

$id = (int) ($_GET['id'] ?? 0);
$r = mysqli_query($con, "SELECT * FROM members WHERE member_id=$id AND gym_id=$gym_id");
$member = $r ? mysqli_fetch_assoc($r) : null;
if (!$member) {
    flash_set('error', 'Member not found.');
    header("Location: index.php");
    exit();
}

$trainers = [];
$r = mysqli_query($con, "SELECT trainer_id, full_name FROM trainers WHERE gym_id=$gym_id AND status='active' ORDER BY full_name");
while ($row = mysqli_fetch_assoc($r)) { $trainers[] = $row; }

$errors = [];

// ---- Activate / deactivate (separate small form) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $newStatus = $member['status'] === 'active' ? 'inactive' : 'active';
    mysqli_query($con, "UPDATE members SET status='" . esc($con, $newStatus) . "' WHERE member_id=$id AND gym_id=$gym_id");
    flash_set('success', "Member marked $newStatus.");
    header("Location: view.php?id=$id");
    exit();
}

// ---- Edit details ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_details'])) {
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $gender     = $_POST['gender'] ?? '';
    $dob        = $_POST['dob'] ?? '';
    $address    = trim($_POST['address'] ?? '');
    $trainer_id = $_POST['trainer_id'] ?? '';

    if ($full_name === '') $errors[] = "Full name is required.";
    if ($phone === '') $errors[] = "Phone number is required.";
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";
    if (!in_array($gender, ['male', 'female', 'other', ''], true)) $gender = '';

    if (empty($errors)) {
        $fn = esc($con, $full_name);
        $em = esc($con, $email);
        $ph = esc($con, $phone);
        $ge = $gender !== '' ? "'" . esc($con, $gender) . "'" : "NULL";
        $db = $dob !== '' ? "'" . esc($con, $dob) . "'" : "NULL";
        $ad = esc($con, $address);
        $tr = ($trainer_id !== '' && ctype_digit((string)$trainer_id)) ? (int) $trainer_id : "NULL";

        $sql = "UPDATE members SET full_name='$fn', email=" . ($em !== '' ? "'$em'" : "NULL") . ",
                phone='$ph', gender=$ge, dob=$db, address='$ad', trainer_id=$tr
                WHERE member_id=$id AND gym_id=$gym_id";

        if (mysqli_query($con, $sql)) {
            flash_set('success', 'Member details updated.');
            header("Location: view.php?id=$id");
            exit();
        } else {
            $errors[] = "Update failed: " . mysqli_error($con);
        }
    } else {
        $member = array_merge($member, compact('full_name','email','phone','gender','dob','address','trainer_id'));
    }
}

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Edit <?php echo h($member['full_name']); ?></h2>
    <p class="section-sub">Update profile details or change membership status below.</p>
  </div>
  <a class="btn" href="view.php?id=<?php echo $id; ?>">← Back to profile</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash--error reveal"><?php echo implode('<br>', array_map('h', $errors)); ?></div>
<?php endif; ?>

<form class="form-card reveal" method="post" style="animation-delay:0.05s">
  <input type="hidden" name="save_details" value="1">
  <div class="field-row">
    <div class="field">
      <label for="full_name">Full name *</label>
      <input class="input" type="text" id="full_name" name="full_name" value="<?php echo h($member['full_name']); ?>" required>
    </div>
    <div class="field">
      <label for="phone">Phone *</label>
      <input class="input" type="text" id="phone" name="phone" value="<?php echo h($member['phone']); ?>" required>
    </div>
  </div>

  <div class="field-row">
    <div class="field">
      <label for="email">Email</label>
      <input class="input" type="email" id="email" name="email" value="<?php echo h($member['email']); ?>">
    </div>
    <div class="field">
      <label for="gender">Gender</label>
      <select class="input" id="gender" name="gender">
        <option value="">Prefer not to say</option>
        <option value="male" <?php echo $member['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
        <option value="female" <?php echo $member['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
        <option value="other" <?php echo $member['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
      </select>
    </div>
  </div>

  <div class="field-row">
    <div class="field">
      <label for="dob">Date of birth</label>
      <input class="input" type="date" id="dob" name="dob" value="<?php echo h($member['dob']); ?>">
    </div>
    <div class="field">
      <label for="trainer_id">Assigned trainer</label>
      <select class="input" id="trainer_id" name="trainer_id">
        <option value="">No trainer</option>
        <?php foreach ($trainers as $t): ?>
          <option value="<?php echo (int) $t['trainer_id']; ?>" <?php echo (string) $member['trainer_id'] === (string) $t['trainer_id'] ? 'selected' : ''; ?>><?php echo h($t['full_name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="field">
    <label for="address">Address</label>
    <textarea class="input" id="address" name="address"><?php echo h($member['address']); ?></textarea>
  </div>

  <div class="form-actions">
    <button class="btn btn--primary" type="submit">Save changes</button>
    <a class="btn" href="view.php?id=<?php echo $id; ?>">Cancel</a>
  </div>
</form>

<div class="form-card reveal" style="animation-delay:0.1s">
  <div class="field">
    <label>Membership status</label>
    <p class="section-sub" style="margin-bottom:12px;">
      Currently <strong><?php echo h(ucfirst($member['status'])); ?></strong>.
      Deactivating hides the member from active rosters without deleting their history.
    </p>
  </div>
  <form method="post">
    <input type="hidden" name="toggle_status" value="1">
    <button class="btn <?php echo $member['status'] === 'active' ? 'btn--danger' : 'btn--primary'; ?>" type="submit">
      <?php echo $member['status'] === 'active' ? 'Deactivate member' : 'Activate member'; ?>
    </button>
  </form>
</div>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
