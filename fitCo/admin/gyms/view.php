<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Gym detail';
$activeNav = 'gyms';

$gymViewId = (int) ($_GET['id'] ?? 0);
$r = mysqli_query($con, "SELECT * FROM gym WHERE gym_id=$gymViewId");
$gymRow = $r ? mysqli_fetch_assoc($r) : null;

if (!$gymRow) {
    flash_set('error', 'Gym not found.');
    header("Location: index.php");
    exit();
}

// ---- Handle status-change actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $map = [
        'approve'   => 'approved',
        'reject'    => 'rejected',
        'suspend'   => 'suspended',
        'reinstate' => 'approved',
    ];
    if (isset($map[$_POST['action']])) {
        mysqli_query($con, "UPDATE gym SET status='" . $map[$_POST['action']] . "' WHERE gym_id=$gymViewId");

        // Newly approved (fresh approval or a reinstated gym) — notify the owner by email.
        if ($map[$_POST['action']] === 'approved') {
            send_gym_approval_email($gymRow['gym_email'], $gymRow['owner_name'], $gymRow['gym_name']);
        }

        flash_set('success', 'Gym status updated to ' . $map[$_POST['action']] . '.');
        header("Location: view.php?id=$gymViewId");
        exit();
    }
}

$memberCount = (int) (mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) c FROM members WHERE gym_id=$gymViewId"))['c'] ?? 0);
$planCount   = (int) (mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) c FROM membership_plans WHERE gym_id=$gymViewId"))['c'] ?? 0);
$revenue     = (float) (mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COALESCE(SUM(p.amount),0) s FROM payments p JOIN members m ON m.member_id=p.member_id WHERE m.gym_id=$gymViewId AND p.status='paid'"
))['s'] ?? 0);

require __DIR__ . "/../includes/layout-top.php";

$badgeClass = [
    'pending'   => 'badge--warn',
    'approved'  => 'badge--success',
    'rejected'  => 'badge--error',
    'suspended' => 'badge--error',
];
?>

<div class="section-head reveal">
  <div>
    <h2><?php echo h($gymRow['gym_name']); ?></h2>
    <p class="section-sub">
      <span class="badge <?php echo $badgeClass[$gymRow['status']] ?? ''; ?>"><?php echo h(ucfirst($gymRow['status'])); ?></span>
      · Registered <?php echo fmt_date($gymRow['created_at']); ?>
    </p>
  </div>
  <a class="btn" href="index.php">← Back to gyms</a>
</div>

<?php if ($gymRow['status'] === 'pending'): ?>
  <div class="panel-card reveal" style="animation-delay:0.05s;">
    <p class="section-sub" style="margin:0;">Member count, plans offered, and revenue collected will show here once this gym is approved.</p>
  </div>
<?php else: ?>
  <div class="stat-grid reveal" style="animation-delay:0.05s">
    <div class="stat-card">
      <div class="stat-card__label">Members</div>
      <div class="stat-card__value"><?php echo $memberCount; ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-card__label">Plans offered</div>
      <div class="stat-card__value"><?php echo $planCount; ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-card__label">Revenue collected</div>
      <div class="stat-card__value"><?php echo fmt_money($revenue); ?></div>
    </div>
  </div>
<?php endif; ?>

<div class="panel-card reveal" style="animation-delay:0.1s">
  <div class="panel-head"><h2>Gym & owner details</h2></div>
  <ul class="list">
    <li class="list-row"><div class="list-main"><span class="list-name">Owner</span><span class="list-sub"><?php echo h($gymRow['owner_name']); ?></span></div></li>
    <li class="list-row"><div class="list-main"><span class="list-name">Email</span><span class="list-sub"><?php echo h($gymRow['gym_email']); ?></span></div></li>
    <li class="list-row"><div class="list-main"><span class="list-name">Phone</span><span class="list-sub"><?php echo h($gymRow['gym_mno']); echo $gymRow['gym_alt_mno'] ? ' / ' . h($gymRow['gym_alt_mno']) : ''; ?></span></div></li>
    <li class="list-row"><div class="list-main"><span class="list-name">Address</span><span class="list-sub"><?php echo h($gymRow['gym_address']); ?></span></div></li>
    <li class="list-row">
      <div class="list-main">
        <span class="list-name">License document</span>
        <span class="list-sub">
          <?php if ($gymRow['gym_license']): ?>
            <a href="/mini-projectTEMP/fitCo/assets/uploads/<?php echo rawurlencode($gymRow['gym_license']); ?>" target="_blank" rel="noopener">
              <?php echo h($gymRow['gym_license']); ?>
            </a>
          <?php else: ?>
            — not provided —
          <?php endif; ?>
        </span>
      </div>
    </li>
  </ul>

  <div class="form-actions" style="margin-top:20px; gap:10px; display:flex; flex-wrap:wrap;">
    <?php if ($gymRow['status'] === 'pending'): ?>
      <form method="post"><input type="hidden" name="action" value="approve"><button class="btn btn--primary" type="submit">Approve gym</button></form>
      <form method="post"><input type="hidden" name="action" value="reject"><button class="btn btn--danger" type="submit">Reject application</button></form>
    <?php elseif ($gymRow['status'] === 'approved'): ?>
      <form method="post" onsubmit="return confirm('Suspend this gym? The owner will be logged out and blocked from logging back in until reinstated.');">
        <input type="hidden" name="action" value="suspend">
        <button class="btn btn--danger" type="submit">Suspend gym</button>
      </form>
    <?php elseif ($gymRow['status'] === 'suspended' || $gymRow['status'] === 'rejected'): ?>
      <form method="post"><input type="hidden" name="action" value="reinstate"><button class="btn btn--primary" type="submit">Reinstate / approve</button></form>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
