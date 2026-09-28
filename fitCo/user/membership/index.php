<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Membership';
$activeNav = 'membership';

if (!$member) {
    header("Location: /mini-projectTEMP/fitCo/user/link-membership/link.php");
    exit();
}

$membership = null;
$daysLeft   = null;
$q = "SELECT mm.*, mp.plan_name, mp.price, mp.description, mp.duration_days
      FROM memberships mm
      JOIN membership_plans mp ON mp.plan_id = mm.plan_id
      WHERE mm.member_id=$member_id
      ORDER BY mm.start_date DESC, mm.membership_id DESC
      LIMIT 1";
$r = mysqli_query($con, $q);
if ($r && mysqli_num_rows($r) > 0) {
    $membership = mysqli_fetch_assoc($r);
    $daysLeft = days_until($membership['end_date']);
}

$historyCount = 0;
$r = mysqli_query($con, "SELECT COUNT(*) c FROM memberships WHERE member_id=$member_id");
$historyCount = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Your membership</h2>
    <p class="section-sub">Plan, benefits, and renewal status at <?php echo h($gym['gym_name']); ?>.</p>
  </div>
  <?php if ($historyCount > 0): ?>
    <a class="btn" href="history.php">Membership history</a>
  <?php endif; ?>
</div>

<?php if (!$membership): ?>

  <div class="panel-card reveal" style="animation-delay:0.05s">
    <div class="panel-head"><h2>No active plan</h2></div>
    <p class="section-sub" style="margin-bottom:14px;">You don't have a membership plan yet. Pick one to get started.</p>
    <a class="btn btn--primary" href="renew.php">Choose a plan</a>
  </div>

<?php else: ?>

  <?php if ($daysLeft <= 7): ?>
  <div class="alert-banner reveal">
    <span>
      <?php if ($daysLeft < 0): ?>
        Your membership expired <?php echo abs($daysLeft); ?> day<?php echo abs($daysLeft) == 1 ? '' : 's'; ?> ago.
      <?php else: ?>
        Your membership expires in <?php echo $daysLeft; ?> day<?php echo $daysLeft == 1 ? '' : 's'; ?>.
      <?php endif; ?>
    </span>
    <a href="renew.php?membership_id=<?php echo (int) $membership['membership_id']; ?>">Renew now →</a>
  </div>
  <?php endif; ?>

  <section class="panels">

    <div class="panel-card reveal" style="animation-delay:0.1s">
      <div class="panel-head">
        <h2><?php echo h($membership['plan_name']); ?></h2>
        <span class="badge <?php echo ($membership['status'] === 'active' && $daysLeft >= 0) ? 'badge--success' : ($daysLeft < 0 ? 'badge--error' : 'badge--warn'); ?>">
          <?php echo h(ucfirst($daysLeft < 0 ? 'expired' : $membership['status'])); ?>
        </span>
      </div>
      <ul class="list">
        <li class="list-row">
          <div class="list-main"><span class="list-name">Price</span><span class="list-sub"><?php echo fmt_money($membership['price']); ?> / <?php echo (int) $membership['duration_days']; ?> days</span></div>
        </li>
        <li class="list-row">
          <div class="list-main"><span class="list-name">Benefits</span><span class="list-sub"><?php echo $membership['description'] ? h($membership['description']) : 'Standard access'; ?></span></div>
        </li>
        <li class="list-row">
          <div class="list-main"><span class="list-name">Start date</span><span class="list-sub"><?php echo fmt_date($membership['start_date']); ?></span></div>
        </li>
        <li class="list-row">
          <div class="list-main"><span class="list-name">Expiry date</span><span class="list-sub"><?php echo fmt_date($membership['end_date']); ?></span></div>
        </li>
        <li class="list-row">
          <div class="list-main"><span class="list-name">Days remaining</span><span class="list-sub"><?php echo max($daysLeft, 0); ?> day<?php echo max($daysLeft, 0) == 1 ? '' : 's'; ?></span></div>
        </li>
      </ul>
      <div class="form-actions" style="margin-top:16px;">
        <a class="btn btn--primary" href="renew.php?membership_id=<?php echo (int) $membership['membership_id']; ?>">Renew membership</a>
      </div>
    </div>

  </section>

<?php endif; ?>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
