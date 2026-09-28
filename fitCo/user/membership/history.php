<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Membership History';
$activeNav = 'membership';

if (!$member) {
    header("Location: /mini-projectTEMP/fitCo/user/link-membership/link.php");
    exit();
}

$rows = [];
$r = mysqli_query($con, "SELECT mm.*, mp.plan_name, mp.price
                          FROM memberships mm
                          JOIN membership_plans mp ON mp.plan_id = mm.plan_id
                          WHERE mm.member_id=$member_id
                          ORDER BY mm.start_date DESC, mm.membership_id DESC");
while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Membership history</h2>
    <p class="section-sub">Every membership period on record, most recent first.</p>
  </div>
  <a class="btn" href="index.php">← Back to membership</a>
</div>

<div class="table-wrap reveal">
  <?php if (empty($rows)): ?>
    <div class="empty-state">No membership history yet.</div>
  <?php else: ?>
    <table class="data">
      <thead>
        <tr>
          <th>Plan</th>
          <th>Start</th>
          <th>End</th>
          <th>Status</th>
          <th>Renewal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $m): ?>
          <tr>
            <td class="table-name"><?php echo h($m['plan_name']); ?> <span class="table-sub"><?php echo fmt_money($m['price']); ?></span></td>
            <td><?php echo fmt_date($m['start_date']); ?></td>
            <td><?php echo fmt_date($m['end_date']); ?></td>
            <td>
              <span class="badge <?php echo $m['status'] === 'active' ? 'badge--success' : ($m['status'] === 'expired' ? 'badge--error' : ''); ?>">
                <?php echo h(ucfirst($m['status'])); ?>
              </span>
            </td>
            <td><?php echo $m['is_renewal'] ? 'Renewal' : 'Original'; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
