<?php
require __DIR__ . "/../includes/bootstrap.php";
$pageTitle = 'Memberships';
$activeNav = 'memberships';

$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'active', 'expired', 'expiring'], true)) $filter = 'all';

// Latest membership per member, joined with member + plan info
$sql = "SELECT mm.*, mp.plan_name, mp.price, me.full_name, me.phone
        FROM memberships mm
        JOIN membership_plans mp ON mp.plan_id = mm.plan_id
        JOIN members me ON me.member_id = mm.member_id
        WHERE me.gym_id = $gym_id
        AND mm.membership_id = (
            SELECT mm2.membership_id FROM memberships mm2
            WHERE mm2.member_id = mm.member_id
            ORDER BY mm2.start_date DESC, mm2.membership_id DESC LIMIT 1
        )
        ORDER BY mm.end_date ASC";
$r = mysqli_query($con, $sql);

$rows = [];
$counts = ['all' => 0, 'active' => 0, 'expired' => 0, 'expiring' => 0];
while ($row = mysqli_fetch_assoc($r)) {
    $d = days_until($row['end_date']);
    if ($row['status'] === 'cancelled') {
        $row['computed'] = 'cancelled';
    } elseif ($d < 0) {
        $row['computed'] = 'expired';
        $counts['expired']++;
    } elseif ($d <= 7) {
        $row['computed'] = 'expiring';
        $counts['expiring']++;
        $counts['active']++;
    } else {
        $row['computed'] = 'active';
        $counts['active']++;
    }
    $counts['all']++;
    $rows[] = $row;
}

$filtered = array_filter($rows, function ($row) use ($filter) {
    if ($filter === 'all') return true;
    if ($filter === 'active') return in_array($row['computed'], ['active', 'expiring'], true);
    return $row['computed'] === $filter;
});

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Memberships</h2>
    <p class="section-sub">Each member's current membership period, across every plan.</p>
  </div>
  <a class="btn btn--primary" href="/mini-projectTEMP/fitCo/owner/plans/index.php">Manage plans →</a>
</div>

<div class="toolbar reveal" style="animation-delay:0.05s">
  <a class="btn <?php echo $filter === 'all' ? 'btn--primary' : ''; ?>" href="?filter=all">All (<?php echo $counts['all']; ?>)</a>
  <a class="btn <?php echo $filter === 'active' ? 'btn--primary' : ''; ?>" href="?filter=active">Active (<?php echo $counts['active']; ?>)</a>
  <a class="btn <?php echo $filter === 'expiring' ? 'btn--primary' : ''; ?>" href="?filter=expiring">Expiring soon (<?php echo $counts['expiring']; ?>)</a>
  <a class="btn <?php echo $filter === 'expired' ? 'btn--primary' : ''; ?>" href="?filter=expired">Expired (<?php echo $counts['expired']; ?>)</a>
</div>

<div class="table-wrap reveal" style="animation-delay:0.1s">
  <?php if (empty($filtered)): ?>
    <div class="empty-state">No memberships in this view.</div>
  <?php else: ?>
  <table class="data">
    <thead><tr><th>Member</th><th>Plan</th><th>Start</th><th>End</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($filtered as $m): ?>
        <tr>
          <td><a class="table-name" href="/mini-projectTEMP/fitCo/owner/members/view.php?id=<?php echo (int) $m['member_id']; ?>"><?php echo h($m['full_name']); ?></a></td>
          <td><?php echo h($m['plan_name']); ?> <span class="table-sub"><?php echo fmt_money($m['price']); ?></span></td>
          <td><?php echo fmt_date($m['start_date']); ?></td>
          <td><?php echo fmt_date($m['end_date']); ?></td>
          <td>
            <?php $badges = ['active' => 'badge--success', 'expiring' => 'badge--warn', 'expired' => 'badge--error', 'cancelled' => '']; ?>
            <span class="badge <?php echo $badges[$m['computed']]; ?>"><?php echo h(ucfirst($m['computed'])); ?></span>
          </td>
          <td class="row-actions">
            <?php if (in_array($m['computed'], ['expiring', 'expired'], true)): ?>
              <a href="/mini-projectTEMP/fitCo/owner/renewals/renew.php?membership_id=<?php echo (int) $m['membership_id']; ?>">Renew</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
