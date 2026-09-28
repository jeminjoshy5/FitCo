<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Workout Plan';
$activeNav = 'workout';

if (!$member) {
    header("Location: /mini-projectTEMP/fitCo/user/link-membership/link.php");
    exit();
}

$plans = [];
$r = mysqli_query($con, "SELECT wp.*, t.full_name AS trainer_name
                          FROM workout_plans wp
                          LEFT JOIN trainers t ON t.trainer_id = wp.trainer_id
                          WHERE wp.member_id=$member_id
                          ORDER BY wp.assigned_date DESC, wp.workout_plan_id DESC");
while ($row = mysqli_fetch_assoc($r)) { $plans[] = $row; }

$current = $plans[0] ?? null;
$previous = array_slice($plans, 1);

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Workout plan</h2>
    <p class="section-sub">Assigned by your trainer at <?php echo h($gym['gym_name']); ?>.</p>
  </div>
</div>

<?php if (!$current): ?>

  <div class="panel-card reveal" style="animation-delay:0.05s">
    <div class="panel-head"><h2>No workout plan yet</h2></div>
    <p class="section-sub">Ask your trainer to assign one — it'll show up here once they do.</p>
  </div>

<?php else: ?>

  <div class="panel-card reveal" style="animation-delay:0.05s">
    <div class="panel-head">
      <h2><?php echo h($current['title']); ?></h2>
      <span class="badge">Current</span>
    </div>
    <ul class="list">
      <li class="list-row">
        <div class="list-main"><span class="list-name">Trainer</span><span class="list-sub"><?php echo $current['trainer_name'] ? h($current['trainer_name']) : 'Unassigned'; ?></span></div>
      </li>
      <li class="list-row">
        <div class="list-main"><span class="list-name">Assigned</span><span class="list-sub"><?php echo fmt_date($current['assigned_date']); ?></span></div>
      </li>
    </ul>
    <?php if (!empty($current['details'])): ?>
      <div class="section-sub" style="margin-top:14px; white-space:pre-wrap; line-height:1.6;"><?php echo h($current['details']); ?></div>
    <?php endif; ?>
  </div>

  <?php if (!empty($previous)): ?>
    <div class="section-head reveal" style="margin-top:28px;">
      <div><h2>Previous plans</h2></div>
    </div>
    <section class="panels">
      <?php foreach ($previous as $p): ?>
        <div class="panel-card reveal">
          <div class="panel-head"><h2><?php echo h($p['title']); ?></h2></div>
          <ul class="list">
            <li class="list-row">
              <div class="list-main"><span class="list-name">Trainer</span><span class="list-sub"><?php echo $p['trainer_name'] ? h($p['trainer_name']) : 'Unassigned'; ?></span></div>
            </li>
            <li class="list-row">
              <div class="list-main"><span class="list-name">Assigned</span><span class="list-sub"><?php echo fmt_date($p['assigned_date']); ?></span></div>
            </li>
          </ul>
          <?php if (!empty($p['details'])): ?>
            <div class="section-sub" style="margin-top:14px; white-space:pre-wrap; line-height:1.6;"><?php echo h($p['details']); ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
