<?php
require __DIR__ . "/../includes/bootstrap.php";
$pageTitle = 'Trainers';
$activeNav = 'trainers';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trainer'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $spec = trim($_POST['specialization'] ?? '');

    if ($full_name === '') $errors[] = "Trainer name is required.";

    if (empty($errors)) {
        $fn = esc($con, $full_name);
        $em = esc($con, $email);
        $ph = esc($con, $phone);
        $sp = esc($con, $spec);
        mysqli_query($con, "INSERT INTO trainers (gym_id, full_name, email, phone, specialization, status)
                             VALUES ($gym_id, '$fn', " . ($em !== '' ? "'$em'" : "NULL") . ", " . ($ph !== '' ? "'$ph'" : "NULL") . ", '$sp', 'active')");
        flash_set('success', 'Trainer added.');
        header("Location: index.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $tid = (int) $_POST['toggle_id'];
    $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT status FROM trainers WHERE trainer_id=$tid AND gym_id=$gym_id"));
    if ($row) {
        $new = $row['status'] === 'active' ? 'inactive' : 'active';
        mysqli_query($con, "UPDATE trainers SET status='$new' WHERE trainer_id=$tid AND gym_id=$gym_id");
        flash_set('success', "Trainer marked $new.");
    }
    header("Location: index.php");
    exit();
}

$trainers = [];
$r = mysqli_query($con, "SELECT tr.*, (SELECT COUNT(*) FROM members me WHERE me.trainer_id = tr.trainer_id) AS member_count
                          FROM trainers tr WHERE tr.gym_id=$gym_id ORDER BY tr.status DESC, tr.full_name");
while ($row = mysqli_fetch_assoc($r)) { $trainers[] = $row; }

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Trainers</h2>
    <p class="section-sub">Staff you can assign to members and workout plans.</p>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash--error reveal"><?php echo implode('<br>', array_map('h', $errors)); ?></div>
<?php endif; ?>

<div class="panels" style="grid-template-columns:1fr 1.4fr;">
  <div class="panel-card reveal">
    <div class="panel-head"><h2>Add a trainer</h2></div>
    <form method="post">
      <input type="hidden" name="add_trainer" value="1">
      <div class="field">
        <label for="full_name">Name *</label>
        <input class="input" type="text" id="full_name" name="full_name" required>
      </div>
      <div class="field">
        <label for="specialization">Specialization</label>
        <input class="input" type="text" id="specialization" name="specialization" placeholder="e.g. Strength, Yoga, CrossFit">
      </div>
      <div class="field-row">
        <div class="field">
          <label for="phone">Phone</label>
          <input class="input" type="text" id="phone" name="phone">
        </div>
        <div class="field">
          <label for="email">Email</label>
          <input class="input" type="email" id="email" name="email">
        </div>
      </div>
      <div class="form-actions">
        <button class="btn btn--primary" type="submit">Add trainer</button>
      </div>
    </form>
  </div>

  <div class="table-wrap reveal" style="animation-delay:0.05s">
    <?php if (empty($trainers)): ?>
      <div class="empty-state">No trainers yet.</div>
    <?php else: ?>
    <table class="data">
      <thead><tr><th>Name</th><th>Specialization</th><th>Contact</th><th>Members</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($trainers as $t): ?>
          <tr>
            <td class="table-name"><?php echo h($t['full_name']); ?></td>
            <td><?php echo h($t['specialization'] ?: '—'); ?></td>
            <td class="table-sub"><?php echo h($t['phone'] ?: $t['email'] ?: '—'); ?></td>
            <td><?php echo (int) $t['member_count']; ?></td>
            <td><span class="badge <?php echo $t['status'] === 'active' ? 'badge--success' : ''; ?>"><?php echo h(ucfirst($t['status'])); ?></span></td>
            <td class="row-actions">
              <form method="post">
                <input type="hidden" name="toggle_id" value="<?php echo (int) $t['trainer_id']; ?>">
                <button type="submit" style="border:none;background:none;text-decoration:underline;color:var(--muted);cursor:pointer;font-size:12.5px;">
                  <?php echo $t['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
