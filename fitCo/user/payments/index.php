<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Payments';
$activeNav = 'payments';

if (!$member) {
    header("Location: /mini-projectTEMP/fitCo/user/link-membership/link.php");
    exit();
}

$filter = $_GET['status'] ?? 'all';
if (!in_array($filter, ['all', 'paid', 'pending'], true)) $filter = 'all';

$where = "p.member_id=$member_id";
if ($filter !== 'all') $where .= " AND p.status='" . esc($con, $filter) . "'";

$rows = [];
$r = mysqli_query($con, "SELECT p.*, mp.plan_name
                          FROM payments p
                          LEFT JOIN memberships mm ON mm.membership_id = p.membership_id
                          LEFT JOIN membership_plans mp ON mp.plan_id = mm.plan_id
                          WHERE $where
                          ORDER BY p.payment_date DESC, p.payment_id DESC");
while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }

$pendingTotal = 0;
$r = mysqli_query($con, "SELECT COALESCE(SUM(amount),0) t FROM payments WHERE member_id=$member_id AND status='pending'");
$pendingTotal = (float) (mysqli_fetch_assoc($r)['t'] ?? 0);

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Payments</h2>
    <p class="section-sub">
      <?php echo $pendingTotal > 0 ? fmt_money($pendingTotal) . ' pending across your account.' : 'You have no pending payments.'; ?>
    </p>
  </div>
</div>

<div class="tabs reveal" style="animation-delay:0.05s">
  <a class="tab <?php echo $filter === 'all' ? 'tab--active' : ''; ?>" href="?status=all">All</a>
  <a class="tab <?php echo $filter === 'paid' ? 'tab--active' : ''; ?>" href="?status=paid">Paid</a>
  <a class="tab <?php echo $filter === 'pending' ? 'tab--active' : ''; ?>" href="?status=pending">Pending</a>
</div>

<div class="table-wrap reveal">
  <?php if (empty($rows)): ?>
    <div class="empty-state">No payments to show.</div>
  <?php else: ?>
    <table class="data">
      <thead>
        <tr>
          <th>Date</th>
          <th>Plan</th>
          <th>Method</th>
          <th>Amount</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $p): ?>
          <tr>
            <td><?php echo fmt_date($p['payment_date']); ?></td>
            <td class="table-name"><?php echo h($p['plan_name'] ?: '—'); ?></td>
            <td><?php echo h(ucfirst($p['payment_method'])); ?></td>
            <td><?php echo fmt_money($p['amount']); ?></td>
            <td>
              <span class="badge <?php echo $p['status'] === 'paid' ? 'badge--success' : 'badge--warn'; ?>">
                <?php echo h(ucfirst($p['status'])); ?>
              </span>
            </td>
            <td>
              <?php if ($p['status'] === 'paid'): ?>
                <a class="panel-link" href="receipt.php?id=<?php echo (int) $p['payment_id']; ?>">Receipt →</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
