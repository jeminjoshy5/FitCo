<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Trainer';
$activeNav = 'trainer';

if (!$member) {
    header("Location: /mini-projectTEMP/fitCo/user/link-membership/link.php");
    exit();
}

$trainer = null;
if (!empty($member['trainer_id'])) {
    $r = mysqli_query($con, "SELECT * FROM trainers WHERE trainer_id=" . (int) $member['trainer_id']);
    if ($r && mysqli_num_rows($r) > 0) { $trainer = mysqli_fetch_assoc($r); }
}

$otherTrainers = [];
$r = mysqli_query($con, "SELECT trainer_id, full_name, specialization FROM trainers WHERE gym_id=$gym_id AND status='active'" .
                         ($trainer ? " AND trainer_id != " . (int) $trainer['trainer_id'] : "") . " ORDER BY full_name");
while ($row = mysqli_fetch_assoc($r)) { $otherTrainers[] = $row; }

$pendingRequest = null;
$r = mysqli_query($con, "SELECT * FROM trainer_change_requests WHERE member_id=$member_id AND status='pending' ORDER BY created_at DESC LIMIT 1");
if ($r && mysqli_num_rows($r) > 0) { $pendingRequest = mysqli_fetch_assoc($r); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_change'])) {
    $requested_trainer_id = $_POST['requested_trainer_id'] !== '' ? (int) $_POST['requested_trainer_id'] : null;
    $note = trim($_POST['note'] ?? '');

    $rtSql = $requested_trainer_id ? $requested_trainer_id : 'NULL';
    $ctSql = $trainer ? (int) $trainer['trainer_id'] : 'NULL';
    $noteEsc = esc($con, $note);

    mysqli_query($con, "INSERT INTO trainer_change_requests (member_id, gym_id, current_trainer_id, requested_trainer_id, note)
                         VALUES ($member_id, $gym_id, $ctSql, $rtSql, " . ($noteEsc !== '' ? "'$noteEsc'" : "NULL") . ")");

    flash_set('success', 'Your request has been sent to the gym — they\'ll follow up on the reassignment.');
    header("Location: index.php");
    exit();
}

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Your trainer</h2>
    <p class="section-sub">Assigned by <?php echo h($gym['gym_name']); ?>.</p>
  </div>
</div>

<section class="panels">

  <div class="panel-card reveal" style="animation-delay:0.05s">
    <div class="panel-head"><h2>Profile</h2></div>
    <?php if ($trainer): ?>
      <ul class="list">
        <li class="list-row">
          <div class="list-main"><span class="list-name"><?php echo h($trainer['full_name']); ?></span><span class="list-sub"><?php echo h($trainer['specialization'] ?: 'Trainer'); ?></span></div>
          <span class="badge <?php echo $trainer['status'] === 'active' ? 'badge--success' : ''; ?>"><?php echo h(ucfirst($trainer['status'])); ?></span>
        </li>
        <li class="list-row">
          <div class="list-main"><span class="list-name">Phone</span><span class="list-sub"><?php echo $trainer['phone'] ? h($trainer['phone']) : 'Not shared'; ?></span></div>
        </li>
        <li class="list-row">
          <div class="list-main"><span class="list-name">Email</span><span class="list-sub"><?php echo $trainer['email'] ? h($trainer['email']) : 'Not shared'; ?></span></div>
        </li>
      </ul>
    <?php else: ?>
      <p class="section-sub">No trainer assigned yet. Ask your gym to assign one, or request one below.</p>
    <?php endif; ?>
  </div>

  <div class="panel-card reveal" style="animation-delay:0.1s">
    <div class="panel-head"><h2>Request a different trainer</h2></div>

    <?php if ($pendingRequest): ?>
      <p class="section-sub">
        You have a pending request from <?php echo fmt_date($pendingRequest['created_at']); ?> —
        your gym hasn't reviewed it yet.
      </p>
    <?php else: ?>
      <form class="form-card" method="post">
        <div class="field">
          <label for="requested_trainer_id">Preferred trainer (optional)</label>
          <select class="input" id="requested_trainer_id" name="requested_trainer_id">
            <option value="">No preference — just reassign me</option>
            <?php foreach ($otherTrainers as $t): ?>
              <option value="<?php echo (int) $t['trainer_id']; ?>"><?php echo h($t['full_name']); ?> — <?php echo h($t['specialization'] ?: 'Trainer'); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="note">Reason (optional)</label>
          <textarea class="input" id="note" name="note" rows="2" placeholder="Let the gym know why, if you'd like."></textarea>
        </div>
        <button type="submit" name="request_change" value="1" class="btn btn--primary">Submit request</button>
      </form>
    <?php endif; ?>
  </div>

</section>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
