<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Register Member';
$activeNav = 'members';

$errors = [];
$full_name = $email = $phone = $gender = $dob = $address = '';
$trainer_id = '';

$trainers = [];
$r = mysqli_query($con, "SELECT trainer_id, full_name FROM trainers WHERE gym_id=$gym_id AND status='active' ORDER BY full_name");
while ($row = mysqli_fetch_assoc($r)) { $trainers[] = $row; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        $sql = "INSERT INTO members (gym_id, full_name, email, phone, gender, dob, address, trainer_id, status, joined_date)
                VALUES ($gym_id, '$fn', " . ($em !== '' ? "'$em'" : "NULL") . ", '$ph', $ge, $db, '$ad', $tr, 'active', CURDATE())";

        if (mysqli_query($con, $sql)) {
            $newId = mysqli_insert_id($con);
            flash_set('success', 'Member registered successfully.');
            header("Location: view.php?id=$newId");
            exit();
        } else {
            $errors[] = "Registration failed: " . mysqli_error($con);
        }
    }
}

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Register a new member</h2>
    <p class="section-sub">Creates the member profile. Assign a plan afterwards from their profile page.</p>
  </div>
  <a class="btn" href="index.php">← Back to members</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash--error reveal"><?php echo implode('<br>', array_map('h', $errors)); ?></div>
<?php endif; ?>

<form class="form-card reveal" method="post" style="animation-delay:0.05s">
  <div class="field-row">
    <div class="field">
      <label for="full_name">Full name *</label>
      <input class="input" type="text" id="full_name" name="full_name" value="<?php echo h($full_name); ?>" required>
    </div>
    <div class="field">
      <label for="phone">Phone *</label>
      <input class="input" type="text" id="phone" name="phone" value="<?php echo h($phone); ?>" required>
    </div>
  </div>

  <div class="field-row">
    <div class="field">
      <label for="email">Email</label>
      <input class="input" type="email" id="email" name="email" value="<?php echo h($email); ?>">
    </div>
    <div class="field">
      <label for="gender">Gender</label>
      <select class="input" id="gender" name="gender">
        <option value="">Prefer not to say</option>
        <option value="male" <?php echo $gender === 'male' ? 'selected' : ''; ?>>Male</option>
        <option value="female" <?php echo $gender === 'female' ? 'selected' : ''; ?>>Female</option>
        <option value="other" <?php echo $gender === 'other' ? 'selected' : ''; ?>>Other</option>
      </select>
    </div>
  </div>

  <div class="field-row">
    <div class="field">
      <label for="dob">Date of birth</label>
      <input class="input" type="date" id="dob" name="dob" value="<?php echo h($dob); ?>">
    </div>
    <div class="field">
      <label for="trainer_id">Assign trainer</label>
      <select class="input" id="trainer_id" name="trainer_id">
        <option value="">No trainer</option>
        <?php foreach ($trainers as $t): ?>
          <option value="<?php echo (int) $t['trainer_id']; ?>" <?php echo (string) $trainer_id === (string) $t['trainer_id'] ? 'selected' : ''; ?>><?php echo h($t['full_name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="field">
    <label for="address">Address</label>
    <textarea class="input" id="address" name="address"><?php echo h($address); ?></textarea>
  </div>

  <div class="form-actions">
    <button class="btn btn--primary" type="submit">Register member</button>
    <a class="btn" href="index.php">Cancel</a>
  </div>
</form>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
