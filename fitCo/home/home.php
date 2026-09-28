<?php
require __DIR__ . "/../owner/includes/bootstrap.php";

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

$first_name = $owner_name !== '' ? explode(' ', trim($owner_name))[0] : 'there';
$hour = (int) date('G');
if ($hour < 12)     { $greeting = "Good morning"; }
elseif ($hour < 17) { $greeting = "Good afternoon"; }
else                { $greeting = "Good evening"; }

// ---------------------------------------------------------------
// Member counts
// ---------------------------------------------------------------
$totalMembers = 0; $activeMembers = 0; $inactiveMembers = 0; $newMembers = 0;

$r = mysqli_query($con, "SELECT status, COUNT(*) c FROM members WHERE gym_id=$gym_id GROUP BY status");
while ($row = mysqli_fetch_assoc($r)) {
    $totalMembers += (int) $row['c'];
    if ($row['status'] === 'active') $activeMembers = (int) $row['c'];
    if ($row['status'] === 'inactive') $inactiveMembers = (int) $row['c'];
}
$r = mysqli_query($con, "SELECT COUNT(*) c FROM members WHERE gym_id=$gym_id AND joined_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$newMembers = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);

// ---------------------------------------------------------------
// Current (latest) membership per member — drives expiring/expired stats
// ---------------------------------------------------------------
$currentMemberships = [];
$sql = "SELECT mm.membership_id, mm.member_id, mm.start_date, mm.end_date, mm.status,
               mp.plan_name, me.full_name
        FROM memberships mm
        JOIN membership_plans mp ON mp.plan_id = mm.plan_id
        JOIN members me ON me.member_id = mm.member_id
        WHERE me.gym_id = $gym_id
        AND mm.membership_id = (
            SELECT mm2.membership_id FROM memberships mm2
            WHERE mm2.member_id = mm.member_id
            ORDER BY mm2.start_date DESC, mm2.membership_id DESC LIMIT 1
        )";
$r = mysqli_query($con, $sql);
while ($row = mysqli_fetch_assoc($r)) { $currentMemberships[] = $row; }

$expiredList = []; $expiringList = []; $activeMembershipCount = 0;
foreach ($currentMemberships as $cm) {
    if ($cm['status'] === 'cancelled') continue;
    $daysLeft = days_until($cm['end_date']);
    if ($daysLeft < 0) {
        $expiredList[] = $cm;
    } elseif ($daysLeft <= 7) {
        $expiringList[] = $cm;
        $activeMembershipCount++;
    } else {
        $activeMembershipCount++;
    }
}
usort($expiringList, fn($a,$b) => strcmp($a['end_date'], $b['end_date']));
usort($expiredList, fn($a,$b) => strcmp($b['end_date'], $a['end_date']));

// ---------------------------------------------------------------
// Attendance / trainers
// ---------------------------------------------------------------
$r = mysqli_query($con, "SELECT COUNT(*) c FROM attendance WHERE gym_id=$gym_id AND check_in_date=CURDATE()");
$todayAttendance = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);

$r = mysqli_query($con, "SELECT COUNT(*) c FROM trainers WHERE gym_id=$gym_id AND status='active'");
$activeTrainers = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);

// ---------------------------------------------------------------
// Revenue / payments
// ---------------------------------------------------------------
$r = mysqli_query($con, "SELECT COALESCE(SUM(p.amount),0) s FROM payments p JOIN members me ON me.member_id=p.member_id
                          WHERE me.gym_id=$gym_id AND p.status='paid' AND p.payment_date = CURDATE()");
$dailyRevenue = (float) (mysqli_fetch_assoc($r)['s'] ?? 0);

$r = mysqli_query($con, "SELECT COALESCE(SUM(p.amount),0) s FROM payments p JOIN members me ON me.member_id=p.member_id
                          WHERE me.gym_id=$gym_id AND p.status='paid'
                          AND MONTH(p.payment_date)=MONTH(CURDATE()) AND YEAR(p.payment_date)=YEAR(CURDATE())");
$monthlyRevenue = (float) (mysqli_fetch_assoc($r)['s'] ?? 0);

$r = mysqli_query($con, "SELECT COUNT(*) c, COALESCE(SUM(p.amount),0) s FROM payments p JOIN members me ON me.member_id=p.member_id
                          WHERE me.gym_id=$gym_id AND p.status='pending'");
$pendingRow = mysqli_fetch_assoc($r);
$pendingCount = (int) ($pendingRow['c'] ?? 0);
$pendingAmount = (float) ($pendingRow['s'] ?? 0);

// ---------------------------------------------------------------
// Recent lists
// ---------------------------------------------------------------
$recentMembers = [];
$r = mysqli_query($con, "SELECT * FROM members WHERE gym_id=$gym_id ORDER BY created_at DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($r)) { $recentMembers[] = $row; }

$recentPayments = [];
$r = mysqli_query($con, "SELECT p.*, me.full_name FROM payments p JOIN members me ON me.member_id=p.member_id
                          WHERE me.gym_id=$gym_id ORDER BY p.created_at DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($r)) { $recentPayments[] = $row; }

$expiryAlerts = array_slice(array_merge($expiredList, $expiringList), 0, 6);

require __DIR__ . "/../owner/includes/layout-top.php";
?>

<section class="greeting reveal">
  <h1><?php echo h($greeting); ?>, <?php echo h($first_name); ?>.</h1>
  <p class="section-sub">Here's how <?php echo h($gym_name); ?> is doing today.</p>
</section>

<?php if (count($expiryAlerts) > 0): ?>
<div class="alert-banner reveal">
  <span>
    <?php echo count($expiredList); ?> membership<?php echo count($expiredList) === 1 ? '' : 's'; ?> expired
    and <?php echo count($expiringList); ?> expiring within 7 days.
  </span>
  <a href="/mini-projectTEMP/fitCo/owner/renewals/index.php">Review renewals →</a>
</div>
<?php endif; ?>

<section class="stats-grid">
  <div class="stat-card reveal" style="animation-delay:0.03s">
    <span class="stat-label">Total Members</span>
    <span class="stat-value"><?php echo $totalMembers; ?></span>
    <span class="stat-sub"><?php echo $newMembers; ?> new in last 30 days</span>
  </div>
  <div class="stat-card reveal" style="animation-delay:0.06s">
    <span class="stat-label">Active Members</span>
    <span class="stat-value stat-value--success"><?php echo $activeMembers; ?></span>
    <span class="stat-sub"><?php echo $totalMembers > 0 ? round($activeMembers / $totalMembers * 100) : 0; ?>% of total</span>
  </div>
  <div class="stat-card reveal" style="animation-delay:0.09s">
    <span class="stat-label">Inactive Members</span>
    <span class="stat-value"><?php echo $inactiveMembers; ?></span>
    <span class="stat-sub">deactivated accounts</span>
  </div>
  <div class="stat-card reveal" style="animation-delay:0.12s">
    <span class="stat-label">Newly Registered</span>
    <span class="stat-value"><?php echo $newMembers; ?></span>
    <span class="stat-sub">last 30 days</span>
  </div>

  <div class="stat-card reveal" style="animation-delay:0.15s">
    <span class="stat-label">Expired Memberships</span>
    <span class="stat-value stat-value--error"><?php echo count($expiredList); ?></span>
    <span class="stat-sub">need renewal</span>
  </div>
  <div class="stat-card reveal" style="animation-delay:0.18s">
    <span class="stat-label">Expiring Soon</span>
    <span class="stat-value stat-value--warn"><?php echo count($expiringList); ?></span>
    <span class="stat-sub">within 7 days</span>
  </div>
  <div class="stat-card reveal" style="animation-delay:0.21s">
    <span class="stat-label">Today's Attendance</span>
    <span class="stat-value"><?php echo $todayAttendance; ?></span>
    <span class="stat-sub">check-ins today</span>
  </div>
  <div class="stat-card reveal" style="animation-delay:0.24s">
    <span class="stat-label">Active Trainers</span>
    <span class="stat-value"><?php echo $activeTrainers; ?></span>
    <span class="stat-sub">on staff</span>
  </div>

  <div class="stat-card reveal" style="animation-delay:0.27s">
    <span class="stat-label">Daily Revenue</span>
    <span class="stat-value stat-value--success"><?php echo fmt_money($dailyRevenue); ?></span>
    <span class="stat-sub">paid today</span>
  </div>
  <div class="stat-card reveal" style="animation-delay:0.30s">
    <span class="stat-label">Monthly Revenue</span>
    <span class="stat-value stat-value--success"><?php echo fmt_money($monthlyRevenue); ?></span>
    <span class="stat-sub"><?php echo date('F'); ?></span>
  </div>
  <div class="stat-card reveal" style="animation-delay:0.33s">
    <span class="stat-label">Pending Payments</span>
    <span class="stat-value stat-value--warn"><?php echo $pendingCount; ?></span>
    <span class="stat-sub"><?php echo fmt_money($pendingAmount); ?> outstanding</span>
  </div>
</section>

<section class="actions reveal" style="animation-delay:0.36s">
  <a class="action-pill action-pill--primary" href="/mini-projectTEMP/fitCo/owner/members/add.php">
    <svg viewBox="0 0 20 20" fill="none"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    Add Member
  </a>
  <a class="action-pill" href="/mini-projectTEMP/fitCo/owner/attendance/index.php">
    <svg viewBox="0 0 20 20" fill="none"><path d="M4 10l4 4 8-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Mark Attendance
  </a>
  <a class="action-pill" href="/mini-projectTEMP/fitCo/owner/renewals/index.php">
    <svg viewBox="0 0 20 20" fill="none"><path d="M4 10a6 6 0 0 1 10.5-4M16 10a6 6 0 0 1-10.5 4M14 4v3h-3M6 16v-3h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Renewals
  </a>
  <a class="action-pill" href="/mini-projectTEMP/fitCo/owner/plans/index.php">
    <svg viewBox="0 0 20 20" fill="none"><rect x="3.5" y="4.5" width="13" height="11" rx="1.6" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 8h13" stroke="currentColor" stroke-width="1.7"/></svg>
    Membership Plans
  </a>
</section>

<section class="panels">

  <div class="panel-card reveal" style="animation-delay:0.4s">
    <div class="panel-head">
      <h2>Membership expiry alerts</h2>
      <a class="panel-link" href="/mini-projectTEMP/fitCo/owner/renewals/index.php">View all</a>
    </div>
    <ul class="list">
      <?php if (empty($expiryAlerts)): ?>
        <li class="list-empty">Nothing expiring in the next 7 days.</li>
      <?php else: foreach ($expiryAlerts as $m): $d = days_until($m['end_date']); ?>
        <li class="list-row">
          <div class="list-main">
            <span class="list-name"><?php echo h($m['full_name']); ?></span>
            <span class="list-sub"><?php echo h($m['plan_name']); ?> plan</span>
          </div>
          <span class="badge <?php echo $d < 0 ? 'badge--error' : 'badge--warn'; ?>">
            <?php echo $d < 0 ? abs($d) . ' day' . (abs($d) == 1 ? '' : 's') . ' overdue' : $d . ' day' . ($d == 1 ? '' : 's') . ' left'; ?>
          </span>
        </li>
      <?php endforeach; endif; ?>
    </ul>
  </div>

  <div class="panel-card reveal" style="animation-delay:0.44s">
    <div class="panel-head">
      <h2>Recent registrations</h2>
      <a class="panel-link" href="/mini-projectTEMP/fitCo/owner/members/index.php">View all</a>
    </div>
    <ul class="list">
      <?php if (empty($recentMembers)): ?>
        <li class="list-empty">No members registered yet.</li>
      <?php else: foreach ($recentMembers as $m): ?>
        <li class="list-row">
          <div class="list-main">
            <span class="list-name"><?php echo h($m['full_name']); ?></span>
            <span class="list-sub">Joined <?php echo fmt_date($m['joined_date']); ?></span>
          </div>
          <span class="badge <?php echo $m['status'] === 'active' ? 'badge--success' : ''; ?>"><?php echo h(ucfirst($m['status'])); ?></span>
        </li>
      <?php endforeach; endif; ?>
    </ul>
  </div>

  <div class="panel-card reveal" style="animation-delay:0.48s">
    <div class="panel-head">
      <h2>Recent payments</h2>
      <a class="panel-link" href="/mini-projectTEMP/fitCo/owner/payments/index.php">View all</a>
    </div>
    <ul class="list">
      <?php if (empty($recentPayments)): ?>
        <li class="list-empty">No payments recorded yet.</li>
      <?php else: foreach ($recentPayments as $p): ?>
        <li class="list-row">
          <div class="list-main">
            <span class="list-name"><?php echo h($p['full_name']); ?></span>
            <span class="list-sub"><?php echo fmt_date($p['payment_date']); ?> · <?php echo h(ucfirst($p['payment_method'])); ?></span>
          </div>
          <span class="badge <?php echo $p['status'] === 'paid' ? 'badge--success' : 'badge--warn'; ?>">
            <?php echo fmt_money($p['amount']); ?>
          </span>
        </li>
      <?php endforeach; endif; ?>
    </ul>
  </div>

  <div class="panel-card reveal" style="animation-delay:0.52s">
    <div class="panel-head">
      <h2>Quick links</h2>
    </div>
    <ul class="list">
      <li class="list-row">
        <div class="list-main"><span class="list-name">Manage members</span><span class="list-sub">Search, filter, edit profiles</span></div>
        <a class="panel-link" href="/mini-projectTEMP/fitCo/owner/members/index.php">Open →</a>
      </li>
      <li class="list-row">
        <div class="list-main"><span class="list-name">Manage trainers</span><span class="list-sub"><?php echo $activeTrainers; ?> active on staff</span></div>
        <a class="panel-link" href="/mini-projectTEMP/fitCo/owner/trainers/index.php">Open →</a>
      </li>
      <li class="list-row">
        <div class="list-main"><span class="list-name">All memberships</span><span class="list-sub">Active, expired, expiring</span></div>
        <a class="panel-link" href="/mini-projectTEMP/fitCo/owner/memberships/index.php">Open →</a>
      </li>
    </ul>
  </div>

</section>

<?php require __DIR__ . "/../owner/includes/layout-bottom.php"; ?>
