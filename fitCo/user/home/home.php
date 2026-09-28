<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

$first_name = explode(' ', trim($full_name))[0];
$hour = (int) date('G');
if ($hour < 12)     { $greeting = "Good morning"; }
elseif ($hour < 17) { $greeting = "Good afternoon"; }
else                { $greeting = "Good evening"; }

// ---------------------------------------------------------------
// BMI / split (already existed)
// ---------------------------------------------------------------
$latest_bmi = null;
$r = mysqli_query($con, "SELECT * FROM bmi_records WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 1");
if ($r && mysqli_num_rows($r) > 0) { $latest_bmi = mysqli_fetch_assoc($r); }

$has_split = false;
$r = mysqli_query($con, "SELECT split_id FROM user_splits WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 1");
if ($r && mysqli_num_rows($r) > 0) { $has_split = true; }

// ---------------------------------------------------------------
// Everything below only applies once a membership is linked.
// ---------------------------------------------------------------
$membership       = null;   // current/latest membership row
$daysLeft         = null;
$membershipHistoryCount = 0;
$trainer          = null;
$workoutPlan      = null;
$todayCheckedIn   = false;
$attendance30     = 0;
$attendanceTotal  = 0;
$recentPayments   = [];
$pendingPayments  = [];
$notifications    = [];

if ($member) {
    // Current (latest) membership
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

    $r = mysqli_query($con, "SELECT COUNT(*) c FROM memberships WHERE member_id=$member_id");
    $membershipHistoryCount = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);

    // Trainer
    if (!empty($member['trainer_id'])) {
        $r = mysqli_query($con, "SELECT * FROM trainers WHERE trainer_id=" . (int) $member['trainer_id']);
        if ($r && mysqli_num_rows($r) > 0) { $trainer = mysqli_fetch_assoc($r); }
    }

    // Current workout plan (most recently assigned)
    $r = mysqli_query($con, "SELECT wp.*, t.full_name AS trainer_name
                              FROM workout_plans wp
                              LEFT JOIN trainers t ON t.trainer_id = wp.trainer_id
                              WHERE wp.member_id=$member_id
                              ORDER BY wp.assigned_date DESC, wp.workout_plan_id DESC
                              LIMIT 1");
    if ($r && mysqli_num_rows($r) > 0) { $workoutPlan = mysqli_fetch_assoc($r); }

    // Attendance
    $today = date('Y-m-d');
    $r = mysqli_query($con, "SELECT 1 FROM attendance WHERE member_id=$member_id AND check_in_date='$today' LIMIT 1");
    $todayCheckedIn = $r && mysqli_num_rows($r) > 0;

    $r = mysqli_query($con, "SELECT COUNT(*) c FROM attendance WHERE member_id=$member_id AND check_in_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $attendance30 = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);

    $r = mysqli_query($con, "SELECT COUNT(*) c FROM attendance WHERE member_id=$member_id");
    $attendanceTotal = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);

    // Payments
    $r = mysqli_query($con, "SELECT * FROM payments WHERE member_id=$member_id ORDER BY payment_date DESC, payment_id DESC LIMIT 5");
    while ($row = mysqli_fetch_assoc($r)) { $recentPayments[] = $row; }

    $r = mysqli_query($con, "SELECT * FROM payments WHERE member_id=$member_id AND status='pending' ORDER BY payment_date ASC");
    while ($row = mysqli_fetch_assoc($r)) { $pendingPayments[] = $row; }

    // ---- Notifications (computed on the fly — no persistent store yet) ----
    if ($membership) {
        if ($daysLeft < 0) {
            $notifications[] = ['type' => 'error', 'msg' => 'Your ' . $membership['plan_name'] . ' membership expired ' . abs($daysLeft) . ' day' . (abs($daysLeft) == 1 ? '' : 's') . ' ago.'];
        } elseif ($daysLeft <= 7) {
            $notifications[] = ['type' => 'warn', 'msg' => 'Your ' . $membership['plan_name'] . ' membership expires in ' . $daysLeft . ' day' . ($daysLeft == 1 ? '' : 's') . '.'];
        }
    }
    foreach ($pendingPayments as $p) {
        $notifications[] = ['type' => 'warn', 'msg' => 'Payment of ' . fmt_money($p['amount']) . ' is pending (' . fmt_date($p['payment_date']) . ').'];
    }
    if ($workoutPlan) {
        $notifications[] = ['type' => 'info', 'msg' => 'Workout plan "' . $workoutPlan['title'] . '" assigned ' . fmt_date($workoutPlan['assigned_date']) . '.'];
    }
}

require __DIR__ . "/../includes/layout-top.php";
?>

<section class="greeting reveal">
  <h1><?php echo h($greeting); ?>, <?php echo h($first_name); ?>.</h1>
  <p class="section-sub">
    <?php echo $gym ? 'Here\'s your snapshot at ' . h($gym['gym_name']) . '.' : "Here's your fitness snapshot."; ?>
  </p>
</section>

<?php if (!$member): ?>

  <div class="alert-banner reveal">
    <span>No gym membership linked to this account yet.</span>
    <a href="/mini-projectTEMP/fitCo/user/link-membership/link.php">Link your membership →</a>
  </div>

<?php else: ?>

  <?php if ($membership && $daysLeft !== null && $daysLeft <= 7): ?>
  <div class="alert-banner reveal">
    <span>
      <?php if ($daysLeft < 0): ?>
        Your membership expired <?php echo abs($daysLeft); ?> day<?php echo abs($daysLeft) == 1 ? '' : 's'; ?> ago.
      <?php else: ?>
        Your membership expires in <?php echo $daysLeft; ?> day<?php echo $daysLeft == 1 ? '' : 's'; ?>.
      <?php endif; ?>
    </span>
  </div>
  <?php endif; ?>

  <section class="stats-grid">
    <div class="stat-card reveal" style="animation-delay:0.03s">
      <span class="stat-label">Membership Status</span>
      <span class="stat-value <?php echo $membership && $membership['status'] === 'active' && $daysLeft >= 0 ? 'stat-value--success' : 'stat-value--error'; ?>">
        <?php echo $membership ? h(ucfirst($daysLeft < 0 ? 'expired' : $membership['status'])) : '—'; ?>
      </span>
      <span class="stat-sub"><?php echo $membership ? h($membership['plan_name']) . ' plan' : 'No membership on record'; ?></span>
    </div>
    <div class="stat-card reveal" style="animation-delay:0.06s">
      <span class="stat-label">Days Remaining</span>
      <span class="stat-value <?php echo $daysLeft !== null && $daysLeft <= 7 ? ($daysLeft < 0 ? 'stat-value--error' : 'stat-value--warn') : ''; ?>">
        <?php echo $daysLeft !== null ? max($daysLeft, 0) : '—'; ?>
      </span>
      <span class="stat-sub">
        <?php echo $membership ? 'expires ' . fmt_date($membership['end_date']) : '—'; ?>
      </span>
    </div>
    <div class="stat-card reveal" style="animation-delay:0.09s">
      <span class="stat-label">Today's Attendance</span>
      <span class="stat-value <?php echo $todayCheckedIn ? 'stat-value--success' : ''; ?>"><?php echo $todayCheckedIn ? 'Checked in' : 'Not yet'; ?></span>
      <span class="stat-sub"><a class="panel-link" href="/mini-projectTEMP/fitCo/user/attendance/index.php"><?php echo $attendance30; ?> check-ins in last 30 days →</a></span>
    </div>
    <div class="stat-card reveal" style="animation-delay:0.12s">
      <span class="stat-label">Pending Payments</span>
      <span class="stat-value <?php echo count($pendingPayments) > 0 ? 'stat-value--warn' : ''; ?>"><?php echo count($pendingPayments); ?></span>
      <span class="stat-sub">
        <?php echo count($pendingPayments) > 0 ? fmt_money(array_sum(array_column($pendingPayments, 'amount'))) . ' outstanding' : 'nothing due'; ?>
      </span>
    </div>
  </section>

  <section class="actions reveal" style="animation-delay:0.16s">
    <a class="action-pill action-pill--primary" href="/mini-projectTEMP/fitCo/user/profile/index.php">
      <svg viewBox="0 0 20 20" fill="none"><circle cx="10" cy="7" r="3" stroke="currentColor" stroke-width="1.7"/><path d="M4 17c1-3.5 4-5 6-5s5 1.5 6 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
      View Profile
    </a>
    <a class="action-pill" href="/mini-projectTEMP/fitCo/user/bmi/bmi.php">
      <svg viewBox="0 0 20 20" fill="none"><rect x="4" y="3" width="12" height="14" rx="1.6" stroke="currentColor" stroke-width="1.7"/><path d="M7 8h6M7 11h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
      BMI Tracker
    </a>
    <a class="action-pill" href="/mini-projectTEMP/fitCo/user/split/split.php">
      <svg viewBox="0 0 20 20" fill="none"><path d="M4 16V8M10 16V4M16 16v-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      Workout Split
    </a>
    <?php if ($membership): ?>
    <a class="action-pill" href="/mini-projectTEMP/fitCo/user/membership/renew.php?membership_id=<?php echo (int) $membership['membership_id']; ?>">
      <svg viewBox="0 0 20 20" fill="none"><path d="M4 10a6 6 0 0 1 10.5-4M16 10a6 6 0 0 1-10.5 4M14 4v3h-3M6 16v-3h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Renew Membership
    </a>
    <?php else: ?>
    <a class="action-pill" href="/mini-projectTEMP/fitCo/user/membership/renew.php">
      <svg viewBox="0 0 20 20" fill="none"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      Choose a Plan
    </a>
    <?php endif; ?>
    <?php if (count($pendingPayments) > 0): ?>
    <a class="action-pill" href="/mini-projectTEMP/fitCo/user/payments/index.php?status=pending">
      <svg viewBox="0 0 20 20" fill="none"><rect x="3.5" y="4.5" width="13" height="11" rx="1.6" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 8h13" stroke="currentColor" stroke-width="1.7"/></svg>
      Pay Pending Dues
    </a>
    <?php endif; ?>
  </section>

  <section class="panels">

    <div class="panel-card reveal" style="animation-delay:0.2s">
      <div class="panel-head">
        <h2>Membership</h2>
      </div>
      <?php if ($membership): ?>
        <ul class="list">
          <li class="list-row">
            <div class="list-main"><span class="list-name">Plan</span><span class="list-sub"><?php echo h($membership['plan_name']); ?> · <?php echo fmt_money($membership['price']); ?></span></div>
          </li>
          <li class="list-row">
            <div class="list-main"><span class="list-name">Start date</span><span class="list-sub"><?php echo fmt_date($membership['start_date']); ?></span></div>
          </li>
          <li class="list-row">
            <div class="list-main"><span class="list-name">Expiry date</span><span class="list-sub"><?php echo fmt_date($membership['end_date']); ?></span></div>
          </li>
          <li class="list-row">
            <div class="list-main"><span class="list-name">History</span><span class="list-sub"><?php echo $membershipHistoryCount; ?> period<?php echo $membershipHistoryCount == 1 ? '' : 's'; ?> on record</span></div>
          </li>
        </ul>
      <?php else: ?>
        <p class="section-sub">No membership plan yet. <a href="/mini-projectTEMP/fitCo/user/membership/renew.php">Choose one →</a></p>
      <?php endif; ?>
    </div>

    <div class="panel-card reveal" style="animation-delay:0.24s">
      <div class="panel-head">
        <h2>Trainer &amp; workout plan</h2>
        <a class="panel-link" href="/mini-projectTEMP/fitCo/user/trainer/index.php">Trainer →</a>
      </div>
      <ul class="list">
        <li class="list-row">
          <div class="list-main">
            <span class="list-name"><?php echo $trainer ? h($trainer['full_name']) : 'No trainer assigned'; ?></span>
            <span class="list-sub"><?php echo $trainer ? h($trainer['specialization'] ?: 'Trainer') : 'Ask your gym to assign one'; ?></span>
          </div>
        </li>
        <?php if ($workoutPlan): ?>
        <li class="list-row">
          <div class="list-main">
            <span class="list-name"><?php echo h($workoutPlan['title']); ?></span>
            <span class="list-sub">assigned <?php echo fmt_date($workoutPlan['assigned_date']); ?><?php echo $workoutPlan['trainer_name'] ? ' by ' . h($workoutPlan['trainer_name']) : ''; ?></span>
          </div>
          <a class="panel-link" href="/mini-projectTEMP/fitCo/user/workout/index.php">Plan →</a>
        </li>
        <?php else: ?>
        <li class="list-empty">No workout plan assigned yet.</li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="panel-card reveal" style="animation-delay:0.28s">
      <div class="panel-head">
        <h2>Recent payments</h2>
        <a class="panel-link" href="/mini-projectTEMP/fitCo/user/payments/index.php">View all</a>
      </div>
      <ul class="list">
        <?php if (empty($recentPayments)): ?>
          <li class="list-empty">No payments recorded yet.</li>
        <?php else: foreach ($recentPayments as $p): ?>
          <li class="list-row">
            <div class="list-main">
              <span class="list-name"><?php echo fmt_money($p['amount']); ?></span>
              <span class="list-sub"><?php echo fmt_date($p['payment_date']); ?> · <?php echo h(ucfirst($p['payment_method'])); ?></span>
            </div>
            <span class="badge <?php echo $p['status'] === 'paid' ? 'badge--success' : 'badge--warn'; ?>"><?php echo h(ucfirst($p['status'])); ?></span>
          </li>
        <?php endforeach; endif; ?>
      </ul>
    </div>

    <div class="panel-card reveal" style="animation-delay:0.32s">
      <div class="panel-head">
        <h2>Notifications</h2>
      </div>
      <ul class="list">
        <?php if (empty($notifications)): ?>
          <li class="list-empty">Nothing new right now.</li>
        <?php else: foreach ($notifications as $n): ?>
          <li class="list-row">
            <div class="list-main"><span class="list-sub"><?php echo h($n['msg']); ?></span></div>
            <span class="badge <?php echo $n['type'] === 'error' ? 'badge--error' : ($n['type'] === 'warn' ? 'badge--warn' : ''); ?>">
              <?php echo $n['type'] === 'error' ? 'Action needed' : ($n['type'] === 'warn' ? 'Heads up' : 'Info'); ?>
            </span>
          </li>
        <?php endforeach; endif; ?>
      </ul>
    </div>

  </section>

<?php endif; ?>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
