<?php
require __DIR__ . "/../includes/bootstrap.php";
$pageTitle = 'New Plan';
$activeNav = 'plans';

$errors = [];
$plan_name = $duration_days = $price = $description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_name = trim($_POST['plan_name'] ?? '');
    $duration_days = trim($_POST['duration_days'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($plan_name === '') $errors[] = "Plan name is required.";
    if (!ctype_digit((string) $duration_days) || (int) $duration_days <= 0) $errors[] = "Duration must be a positive number of days.";
    if (!is_numeric($price) || (float) $price < 0) $errors[] = "Price must be a valid amount.";

    if (empty($errors)) {
        $pn = esc($con, $plan_name);
        $dd = (int) $duration_days;
        $pr = (float) $price;
        $de = esc($con, $description);
        mysqli_query($con, "INSERT INTO membership_plans (gym_id, plan_name, duration_days, price, description, status)
                             VALUES ($gym_id, '$pn', $dd, $pr, '$de', 'active')");
        flash_set('success', 'Plan created.');
        header("Location: index.php");
        exit();
    }
}

require __DIR__ . "/../includes/layout-top.php";
?>
<div class="section-head reveal">
  <div><h2>New membership plan</h2></div>
  <a class="btn" href="index.php">← Back to plans</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash--error reveal"><?php echo implode('<br>', array_map('h', $errors)); ?></div>
<?php endif; ?>

<form class="form-card reveal" method="post">
  <div class="field">
    <label for="plan_name">Plan name *</label>
    <input class="input" type="text" id="plan_name" name="plan_name" value="<?php echo h($plan_name); ?>" placeholder="e.g. Monthly, Quarterly, Annual" required>
  </div>
  <div class="field-row">
    <div class="field">
      <label for="duration_days">Duration (days) *</label>
      <input class="input" type="number" min="1" id="duration_days" name="duration_days" value="<?php echo h($duration_days); ?>" required>
    </div>
    <div class="field">
      <label for="price">Price (₹) *</label>
      <input class="input" type="number" min="0" step="0.01" id="price" name="price" value="<?php echo h($price); ?>" required>
    </div>
  </div>
  <div class="field">
    <label for="description">Description</label>
    <textarea class="input" id="description" name="description"><?php echo h($description); ?></textarea>
  </div>
  <div class="form-actions">
    <button class="btn btn--primary" type="submit">Create plan</button>
    <a class="btn" href="index.php">Cancel</a>
  </div>
</form>
<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
