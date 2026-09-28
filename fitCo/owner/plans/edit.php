<?php
require __DIR__ . "/../includes/bootstrap.php";
$pageTitle = 'Edit Plan';
$activeNav = 'plans';

$id = (int) ($_GET['id'] ?? 0);
$r = mysqli_query($con, "SELECT * FROM membership_plans WHERE plan_id=$id AND gym_id=$gym_id");
$plan = $r ? mysqli_fetch_assoc($r) : null;
if (!$plan) {
    flash_set('error', 'Plan not found.');
    header("Location: index.php");
    exit();
}

$errors = [];
$plan_name = $plan['plan_name'];
$duration_days = $plan['duration_days'];
$price = $plan['price'];
$description = $plan['description'];

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
        mysqli_query($con, "UPDATE membership_plans SET plan_name='$pn', duration_days=$dd, price=$pr, description='$de'
                             WHERE plan_id=$id AND gym_id=$gym_id");
        flash_set('success', 'Plan updated.');
        header("Location: index.php");
        exit();
    }
}

require __DIR__ . "/../includes/layout-top.php";
?>
<div class="section-head reveal">
  <div>
    <h2>Edit plan</h2>
    <?php if ((int) mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) c FROM memberships WHERE plan_id=$id"))['c'] > 0): ?>
      <p class="section-sub">This plan has existing memberships — changes apply going forward only; history is untouched.</p>
    <?php endif; ?>
  </div>
  <a class="btn" href="index.php">← Back to plans</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash--error reveal"><?php echo implode('<br>', array_map('h', $errors)); ?></div>
<?php endif; ?>

<form class="form-card reveal" method="post">
  <div class="field">
    <label for="plan_name">Plan name *</label>
    <input class="input" type="text" id="plan_name" name="plan_name" value="<?php echo h($plan_name); ?>" required>
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
    <button class="btn btn--primary" type="submit">Save changes</button>
    <a class="btn" href="index.php">Cancel</a>
  </div>
</form>
<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
