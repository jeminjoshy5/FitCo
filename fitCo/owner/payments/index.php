<?php
require __DIR__ . "/../includes/bootstrap.php";
$pageTitle = 'Payments';
$activeNav = 'payments';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $pid = (int) $_POST['payment_id'];
    mysqli_query($con, "UPDATE payments p JOIN members me ON me.member_id=p.member_id
                         SET p.status='paid' WHERE p.payment_id=$pid AND me.gym_id=$gym_id");
    flash_set('success', 'Payment marked as paid.');
    header("Location: index.php" . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit();
}

$status = $_GET['status'] ?? 'all';
$where = "me.gym_id=$gym_id";
if (in_array($status, ['paid', 'pending'], true)) {
    $where .= " AND p.status='" . esc($con, $status) . "'";
}

$r = mysqli_query($con, "SELECT p.*, me.full_name FROM payments p JOIN members me ON me.member_id=p.member_id
                          WHERE $where ORDER BY p.payment_date DESC, p.payment_id DESC LIMIT 100");
$payments = [];
while ($row = mysqli_fetch_assoc($r)) { $payments[] = $row; }

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Payments</h2>
    <p class="section-sub">Recorded from plan assignments and renewals.</p>
  </div>
</div>

<div class="toolbar reveal" style="animation-delay:0.05s">
  <a class="btn <?php echo $status === 'all' ? 'btn--primary' : ''; ?>" href="?status=all">All</a>
  <a class="btn <?php echo $status === 'paid' ? 'btn--primary' : ''; ?>" href="?status=paid">Paid</a>
  <a class="btn <?php echo $status === 'pending' ? 'btn--primary' : ''; ?>" href="?status=pending">Pending</a>
</div>

<div class="table-wrap reveal" style="animation-delay:0.1s">
  <?php if (empty($payments)): ?>
    <div class="empty-state">No payments in this view.</div>
  <?php else: ?>
  <table class="data">
    <thead><tr><th>Member</th><th>Date</th><th>Amount</th><th>Method</th><th>Status</th><th>Notes</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($payments as $p): ?>
        <tr>
          <td><a class="table-name" href="/mini-projectTEMP/fitCo/owner/members/view.php?id=<?php echo (int) $p['member_id']; ?>&tab=payments"><?php echo h($p['full_name']); ?></a></td>
          <td><?php echo fmt_date($p['payment_date']); ?></td>
          <td class="table-name"><?php echo fmt_money($p['amount']); ?></td>
          <td><?php echo h(ucfirst(str_replace('_',' ',$p['payment_method']))); ?></td>
          <td><span class="badge <?php echo $p['status'] === 'paid' ? 'badge--success' : 'badge--warn'; ?>"><?php echo h(ucfirst($p['status'])); ?></span></td>
          <td class="table-sub"><?php echo h($p['notes'] ?: '—'); ?></td>
          <td class="row-actions">
            <?php if ($p['status'] === 'pending'): ?>
              <form method="post">
                <input type="hidden" name="mark_paid" value="1">
                <input type="hidden" name="payment_id" value="<?php echo (int) $p['payment_id']; ?>">
                <button type="submit" style="border:none;background:none;text-decoration:underline;color:var(--success);cursor:pointer;font-size:12.5px;">Mark paid</button>
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
