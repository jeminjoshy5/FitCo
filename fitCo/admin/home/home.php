<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

$hour = (int) date('G');
if ($hour < 12)     { $greeting = "Good morning"; }
elseif ($hour < 17) { $greeting = "Good afternoon"; }
else                { $greeting = "Good evening"; }

// ---- Gym counts by approval status ----
$gymCounts = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'suspended' => 0];
$r = mysqli_query($con, "SELECT status, COUNT(*) c FROM gym GROUP BY status");
while ($row = mysqli_fetch_assoc($r)) { $gymCounts[$row['status']] = (int) $row['c']; }
$totalGyms = array_sum($gymCounts);

// ---- Users / members ----
$totalUsers = (int) (mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) c FROM users"))['c'] ?? 0);
$totalMembers = (int) (mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) c FROM members"))['c'] ?? 0);
$activeMemberships = (int) (mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) c FROM memberships WHERE status='active' AND end_date >= CURDATE()"))['c'] ?? 0);

// ---- Platform revenue ----
$totalRevenue = (float) (mysqli_fetch_assoc(mysqli_query($con, "SELECT COALESCE(SUM(amount),0) s FROM payments WHERE status='paid'"))['s'] ?? 0);
$monthRevenue = (float) (mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COALESCE(SUM(amount),0) s FROM payments WHERE status='paid' AND MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())"
))['s'] ?? 0);

// ---- Recent gym signups awaiting review ----
$pendingGyms = [];
$r = mysqli_query($con, "SELECT gym_id, gym_name, owner_name, gym_email, created_at FROM gym WHERE status='pending' ORDER BY created_at DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($r)) { $pendingGyms[] = $row; }

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2><?php echo h($greeting); ?>, <?php echo h(explode(' ', $admin_name)[0]); ?>.</h2>
    <p class="section-sub">Here's how the platform is doing.</p>
  </div>
</div>

<div class="stat-grid reveal" style="animation-delay:0.05s">
  <div class="stat-card">
    <div class="stat-card__label">Total gyms</div>
    <div class="stat-card__value"><?php echo $totalGyms; ?></div>
    <div class="stat-card__sub"><?php echo $gymCounts['approved']; ?> approved · <?php echo $gymCounts['pending']; ?> pending</div>
  </div>
  <div class="stat-card">
    <div class="stat-card__label">Total users</div>
    <div class="stat-card__value"><?php echo $totalUsers; ?></div>
    <div class="stat-card__sub"><?php echo $totalMembers; ?> linked to a gym</div>
  </div>
  <div class="stat-card">
    <div class="stat-card__label">Active memberships</div>
    <div class="stat-card__value"><?php echo $activeMemberships; ?></div>
    <div class="stat-card__sub">across all gyms</div>
  </div>
  <div class="stat-card">
    <div class="stat-card__label">Platform revenue</div>
    <div class="stat-card__value"><?php echo fmt_money($totalRevenue); ?></div>
    <div class="stat-card__sub"><?php echo fmt_money($monthRevenue); ?> this month</div>
  </div>
</div>

<div class="panel-card reveal" style="animation-delay:0.1s">
  <div class="panel-head">
    <h2>Gyms awaiting review</h2>
    <?php if ($gymCounts['pending'] > 0): ?>
      <a class="btn" href="../gyms/index.php?status=pending">View all (<?php echo $gymCounts['pending']; ?>)</a>
    <?php endif; ?>
  </div>
  <?php if (!$pendingGyms): ?>
    <div class="empty-state">No gyms are waiting for approval right now.</div>
  <?php else: ?>
    <table class="data">
      <thead>
        <tr><th>Gym</th><th>Owner</th><th>Email</th><th>Applied</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($pendingGyms as $g): ?>
          <tr>
            <td><a class="table-name" href="../gyms/view.php?id=<?php echo (int) $g['gym_id']; ?>"><?php echo h($g['gym_name']); ?></a></td>
            <td><?php echo h($g['owner_name']); ?></td>
            <td><?php echo h($g['gym_email']); ?></td>
            <td><?php echo fmt_date($g['created_at']); ?></td>
            <td class="row-actions"><a href="../gyms/view.php?id=<?php echo (int) $g['gym_id']; ?>">Review</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
