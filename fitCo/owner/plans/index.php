<?php
require __DIR__ . "/../includes/bootstrap.php";
$pageTitle = 'Membership Plans';
$activeNav = 'plans';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $pid = (int) $_POST['toggle_id'];
    $r = mysqli_query($con, "SELECT status FROM membership_plans WHERE plan_id=$pid AND gym_id=$gym_id");
    if ($row = mysqli_fetch_assoc($r)) {
        $new = $row['status'] === 'active' ? 'inactive' : 'active';
        mysqli_query($con, "UPDATE membership_plans SET status='$new' WHERE plan_id=$pid AND gym_id=$gym_id");
        flash_set('success', "Plan marked $new.");
    }
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $pid = (int) $_POST['delete_id'];
    $inUse = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) c FROM memberships WHERE plan_id=$pid"));
    if ((int) $inUse['c'] > 0) {
        flash_set('error', "Can't delete — this plan has membership history. Deactivate it instead.");
    } else {
        mysqli_query($con, "DELETE FROM membership_plans WHERE plan_id=$pid AND gym_id=$gym_id");
        flash_set('success', 'Plan deleted.');
    }
    header("Location: index.php");
    exit();
}

$plans = [];
$r = mysqli_query($con, "SELECT mp.*,
        (SELECT COUNT(*) FROM memberships m WHERE m.plan_id = mp.plan_id) AS times_assigned
        FROM membership_plans mp WHERE mp.gym_id=$gym_id ORDER BY mp.status DESC, mp.plan_name");
while ($row = mysqli_fetch_assoc($r)) { $plans[] = $row; }

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Membership plans</h2>
    <p class="section-sub">The catalog of plans you can assign to members.</p>
  </div>
  <a class="btn btn--primary" href="add.php">+ New plan</a>
</div>

<div class="table-wrap reveal">
  <?php if (empty($plans)): ?>
    <div class="empty-state">No plans yet — create your first membership plan.</div>
  <?php else: ?>
  <table class="data">
    <thead><tr><th>Plan</th><th>Duration</th><th>Price</th><th>Times assigned</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($plans as $p): ?>
        <tr>
          <td>
            <div class="table-name"><?php echo h($p['plan_name']); ?></div>
            <?php if ($p['description']): ?><div class="table-sub"><?php echo h($p['description']); ?></div><?php endif; ?>
          </td>
          <td><?php echo (int) $p['duration_days']; ?> days</td>
          <td><?php echo fmt_money($p['price']); ?></td>
          <td><?php echo (int) $p['times_assigned']; ?></td>
          <td><span class="badge <?php echo $p['status'] === 'active' ? 'badge--success' : ''; ?>"><?php echo h(ucfirst($p['status'])); ?></span></td>
          <td class="row-actions">
            <a href="edit.php?id=<?php echo (int) $p['plan_id']; ?>">Edit</a>
            <form method="post" style="display:inline">
              <input type="hidden" name="toggle_id" value="<?php echo (int) $p['plan_id']; ?>">
              <button class="btn btn--sm" type="submit" style="padding:2px 0;border:none;background:none;text-decoration:underline;color:var(--muted);cursor:pointer;">
                <?php echo $p['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
              </button>
            </form>
            <?php if ((int) $p['times_assigned'] === 0): ?>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete this plan permanently?');">
                <input type="hidden" name="delete_id" value="<?php echo (int) $p['plan_id']; ?>">
                <button type="submit" style="padding:2px 0;border:none;background:none;text-decoration:underline;color:var(--error);cursor:pointer;font-size:12.5px;">Delete</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
