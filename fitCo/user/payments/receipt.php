<?php
require __DIR__ . "/../includes/bootstrap.php";

if (!$member) {
    header("Location: /mini-projectTEMP/fitCo/user/link-membership/link.php");
    exit();
}

$id = (int) ($_GET['id'] ?? 0);
$r = mysqli_query($con, "SELECT p.*, mp.plan_name, mp.duration_days
                          FROM payments p
                          LEFT JOIN memberships mm ON mm.membership_id = p.membership_id
                          LEFT JOIN membership_plans mp ON mp.plan_id = mm.plan_id
                          WHERE p.payment_id=$id AND p.member_id=$member_id");
$payment = $r ? mysqli_fetch_assoc($r) : null;

if (!$payment) {
    flash_set('error', 'Receipt not found.');
    header("Location: index.php");
    exit();
}

// Pull a payment gateway reference out of the notes field, if present.
$reference = '—';
if (!empty($payment['notes']) && preg_match('/payment\s+(\S+)/i', $payment['notes'], $m)) {
    $reference = $m[1];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt — FITCO</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/mini-projectTEMP/fitCo/user/includes/user.css">
<style>
  body{ background:var(--bg); }
  .receipt{ max-width:520px; margin:48px auto; background:#fff; border:1px solid var(--rule); border-radius:16px; padding:36px; }
  .receipt-head{ display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; }
  .receipt-title{ font-size:20px; font-weight:600; }
  .receipt-sub{ font-size:12.5px; color:var(--muted); margin-top:2px; }
  .receipt-row{ display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--rule); font-size:13.5px; }
  .receipt-row:last-child{ border-bottom:none; }
  .receipt-total{ display:flex; justify-content:space-between; padding-top:16px; margin-top:8px; font-size:16px; font-weight:600; }
  .receipt-actions{ margin-top:28px; display:flex; gap:10px; }
  @media print{
    .receipt-actions{ display:none; }
    .receipt{ border:none; margin:0; }
  }
</style>
</head>
<body>

  <div class="receipt">
    <div class="receipt-head">
      <div>
        <div class="receipt-title"><?php echo h($gym['gym_name']); ?></div>
        <div class="receipt-sub"><?php echo h($gym['gym_address']); ?></div>
      </div>
      <span class="badge badge--success">Paid</span>
    </div>

    <div class="receipt-row"><span>Receipt for</span><span><?php echo h($full_name); ?></span></div>
    <div class="receipt-row"><span>Plan</span><span><?php echo h($payment['plan_name'] ?: '—'); ?></span></div>
    <div class="receipt-row"><span>Payment date</span><span><?php echo fmt_date($payment['payment_date']); ?></span></div>
    <div class="receipt-row"><span>Payment method</span><span><?php echo h(ucfirst($payment['payment_method'])); ?></span></div>
    <div class="receipt-row"><span>Reference</span><span><?php echo h($reference); ?></span></div>

    <div class="receipt-total"><span>Total paid</span><span><?php echo fmt_money($payment['amount']); ?></span></div>

    <div class="receipt-actions">
      <button class="btn btn--primary" onclick="window.print()">Download / Print</button>
      <a class="btn" href="index.php">← Back to payments</a>
    </div>
  </div>

</body>
</html>
