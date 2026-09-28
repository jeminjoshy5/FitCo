<?php
require __DIR__ . "/../includes/bootstrap.php";

$id = (int) ($_GET['id'] ?? 0);
$r = mysqli_query($con, "SELECT me.*, tr.full_name AS trainer_name FROM members me
                          LEFT JOIN trainers tr ON tr.trainer_id = me.trainer_id
                          WHERE me.member_id=$id AND me.gym_id=$gym_id");
$member = $r ? mysqli_fetch_assoc($r) : null;
if (!$member) {
    flash_set('error', 'Member not found.');
    header("Location: index.php");
    exit();
}

$pageTitle = $member['full_name'];
$activeNav = 'members';

$plans = [];
$r = mysqli_query($con, "SELECT plan_id, plan_name, duration_days, price FROM membership_plans WHERE gym_id=$gym_id AND status='active' ORDER BY plan_name");
while ($row = mysqli_fetch_assoc($r)) { $plans[] = $row; }

$trainers = [];
$r = mysqli_query($con, "SELECT trainer_id, full_name FROM trainers WHERE gym_id=$gym_id AND status='active' ORDER BY full_name");
while ($row = mysqli_fetch_assoc($r)) { $trainers[] = $row; }

$errors = [];

// ---- Assign a membership plan to this member ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_plan'])) {
    $plan_id = (int) ($_POST['plan_id'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $payment_status = $_POST['payment_status'] ?? 'paid';

    $pr = mysqli_query($con, "SELECT * FROM membership_plans WHERE plan_id=$plan_id AND gym_id=$gym_id AND status='active'");
    $plan = $pr ? mysqli_fetch_assoc($pr) : null;

    if (!$plan) {
        $errors[] = "Select a valid membership plan.";
    } else {
        $start = date('Y-m-d');
        $end   = date('Y-m-d', strtotime("+{$plan['duration_days']} days", strtotime($start)));

        mysqli_query($con, "INSERT INTO memberships (member_id, plan_id, start_date, end_date, status, is_renewal)
                             VALUES ($id, $plan_id, '$start', '$end', 'active', 0)");
        $membershipId = mysqli_insert_id($con);

        $amount = (float) $plan['price'];
        $method = esc($con, $payment_method);
        $pstatus = in_array($payment_status, ['paid', 'pending'], true) ? $payment_status : 'paid';
        mysqli_query($con, "INSERT INTO payments (member_id, membership_id, amount, payment_method, status, payment_date)
                             VALUES ($id, $membershipId, $amount, '$method', '$pstatus', CURDATE())");

        flash_set('success', "Assigned {$plan['plan_name']} plan, active until " . date('d M Y', strtotime($end)) . ".");
        header("Location: view.php?id=$id&tab=membership");
        exit();
    }
}

// ---- Add a workout plan note ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_workout'])) {
    $title = trim($_POST['title'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $wtrainer = $_POST['trainer_id'] ?? '';

    if ($title === '') {
        $errors[] = "Workout plan title is required.";
    } else {
        $t = esc($con, $title);
        $d = esc($con, $details);
        $tr = ($wtrainer !== '' && ctype_digit((string)$wtrainer)) ? (int) $wtrainer : "NULL";
        mysqli_query($con, "INSERT INTO workout_plans (member_id, trainer_id, title, details, assigned_date)
                             VALUES ($id, $tr, '$t', '$d', CURDATE())");
        flash_set('success', 'Workout plan added.');
        header("Location: view.php?id=$id&tab=workout");
        exit();
    }
}

$tab = $_GET['tab'] ?? 'overview';
if (!in_array($tab, ['overview', 'membership', 'payments', 'attendance', 'workout'], true)) $tab = 'overview';

// Current membership
$r = mysqli_query($con, "SELECT mm.*, mp.plan_name, mp.price FROM memberships mm
                          JOIN membership_plans mp ON mp.plan_id = mm.plan_id
                          WHERE mm.member_id=$id ORDER BY mm.start_date DESC, mm.membership_id DESC LIMIT 1");
$currentMembership = $r ? mysqli_fetch_assoc($r) : null;

// Membership history
$membershipHistory = [];
$r = mysqli_query($con, "SELECT mm.*, mp.plan_name, mp.price FROM memberships mm
                          JOIN membership_plans mp ON mp.plan_id = mm.plan_id
                          WHERE mm.member_id=$id ORDER BY mm.start_date DESC, mm.membership_id DESC");
while ($row = mysqli_fetch_assoc($r)) { $membershipHistory[] = $row; }

// Payment history
$paymentHistory = [];
$r = mysqli_query($con, "SELECT * FROM payments WHERE member_id=$id ORDER BY payment_date DESC, payment_id DESC");
while ($row = mysqli_fetch_assoc($r)) { $paymentHistory[] = $row; }

// Attendance history
$attendanceHistory = [];
$r = mysqli_query($con, "SELECT * FROM attendance WHERE member_id=$id ORDER BY check_in_date DESC LIMIT 60");
while ($row = mysqli_fetch_assoc($r)) { $attendanceHistory[] = $row; }

// Workout plans
$workoutPlans = [];
$r = mysqli_query($con, "SELECT wp.*, tr.full_name AS trainer_name FROM workout_plans wp
                          LEFT JOIN trainers tr ON tr.trainer_id = wp.trainer_id
                          WHERE wp.member_id=$id ORDER BY wp.assigned_date DESC, wp.workout_plan_id DESC");
while ($row = mysqli_fetch_assoc($r)) { $workoutPlans[] = $row; }

require __DIR__ . "/../includes/layout-top.php";
?>

<?php if (!empty($errors)): ?>
  <div class="flash flash--error reveal"><?php echo implode('<br>', array_map('h', $errors)); ?></div>
<?php endif; ?>

<div class="profile-head reveal">
  <div>
    <div class="profile-name"><?php echo h($member['full_name']); ?></div>
    <div class="profile-meta">
      <span><?php echo h($member['phone']); ?></span>
      <span><?php echo h($member['email'] ?: 'No email on file'); ?></span>
      <span class="badge <?php echo $member['status'] === 'active' ? 'badge--success' : ''; ?>"><?php echo h(ucfirst($member['status'])); ?></span>
    </div>
  </div>
  <div class="profile-actions">
    <a class="btn" href="edit.php?id=<?php echo $id; ?>">Edit profile</a>
    <a class="btn" href="/mini-projectTEMP/fitCo/owner/attendance/index.php?q=<?php echo urlencode($member['phone']); ?>">Mark attendance</a>
  </div>
</div>

<div class="kv-grid reveal" style="animation-delay:0.05s">
  <div class="kv">
    <div class="kv-label">Current membership</div>
    <div class="kv-value">
      <?php if ($currentMembership): ?>
        <?php echo h($currentMembership['plan_name']); ?> ·
        <?php $d = days_until($currentMembership['end_date']); ?>
        <?php if ($d < 0): ?>
          <span style="color:var(--error)">expired <?php echo abs($d); ?>d ago</span>
        <?php elseif ($d <= 7): ?>
          <span style="color:var(--warn)"><?php echo $d; ?>d left</span>
        <?php else: ?>
          <span style="color:var(--success)">active</span>
        <?php endif; ?>
      <?php else: ?>
        No plan assigned yet
      <?php endif; ?>
    </div>
  </div>
  <div class="kv">
    <div class="kv-label">Assigned trainer</div>
    <div class="kv-value"><?php echo h($member['trainer_name'] ?? '—'); ?></div>
  </div>
  <div class="kv">
    <div class="kv-label">Registered</div>
    <div class="kv-value"><?php echo fmt_date($member['joined_date']); ?></div>
  </div>
</div>

<div class="tabs reveal" style="animation-delay:0.08s">
  <a class="tab <?php echo $tab === 'overview' ? 'tab--active' : ''; ?>" href="?id=<?php echo $id; ?>&tab=overview">Overview</a>
  <a class="tab <?php echo $tab === 'membership' ? 'tab--active' : ''; ?>" href="?id=<?php echo $id; ?>&tab=membership">Membership history</a>
  <a class="tab <?php echo $tab === 'payments' ? 'tab--active' : ''; ?>" href="?id=<?php echo $id; ?>&tab=payments">Payment history</a>
  <a class="tab <?php echo $tab === 'attendance' ? 'tab--active' : ''; ?>" href="?id=<?php echo $id; ?>&tab=attendance">Attendance</a>
  <a class="tab <?php echo $tab === 'workout' ? 'tab--active' : ''; ?>" href="?id=<?php echo $id; ?>&tab=workout">Workout plans</a>
</div>

<?php if ($tab === 'overview'): ?>

  <div class="panels" style="grid-template-columns:1.3fr 1fr;">
    <div class="panel-card reveal">
      <div class="panel-head"><h2>Assign a membership plan</h2></div>
      <?php if (empty($plans)): ?>
        <p class="section-sub">No active plans yet. <a href="/mini-projectTEMP/fitCo/owner/plans/add.php">Create one first →</a></p>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="assign_plan" value="1">
          <div class="field-row">
            <div class="field">
              <label for="plan_id">Plan</label>
              <select class="input" id="plan_id" name="plan_id" required>
                <?php foreach ($plans as $p): ?>
                  <option value="<?php echo (int) $p['plan_id']; ?>"><?php echo h($p['plan_name']); ?> — <?php echo fmt_money($p['price']); ?> / <?php echo (int) $p['duration_days']; ?>d</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label for="payment_method">Payment method</label>
              <select class="input" id="payment_method" name="payment_method">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="upi">UPI</option>
                <option value="bank_transfer">Bank transfer</option>
              </select>
            </div>
          </div>
          <div class="field">
            <label for="payment_status">Payment status</label>
            <select class="input" id="payment_status" name="payment_status">
              <option value="paid">Paid now</option>
              <option value="pending">Mark as pending</option>
            </select>
          </div>
          <div class="form-actions">
            <button class="btn btn--primary" type="submit">Assign plan</button>
          </div>
        </form>
      <?php endif; ?>
    </div>

    <div class="panel-card reveal" style="animation-delay:0.05s">
      <div class="panel-head"><h2>Recent attendance</h2></div>
      <ul class="list">
        <?php if (empty($attendanceHistory)): ?>
          <li class="list-empty">No check-ins recorded.</li>
        <?php else: foreach (array_slice($attendanceHistory, 0, 5) as $a): ?>
          <li class="list-row">
            <span class="list-name"><?php echo fmt_date($a['check_in_date']); ?></span>
            <span class="badge"><?php echo h(date('g:i A', strtotime($a['check_in_time']))); ?></span>
          </li>
        <?php endforeach; endif; ?>
      </ul>
    </div>
  </div>

  <?php if ($member['address']): ?>
    <div class="panel-card reveal" style="animation-delay:0.1s; max-width:560px;">
      <div class="panel-head"><h2>Address</h2></div>
      <p class="section-sub"><?php echo nl2br(h($member['address'])); ?></p>
    </div>
  <?php endif; ?>

<?php elseif ($tab === 'membership'): ?>

  <div class="table-wrap reveal">
    <?php if (empty($membershipHistory)): ?>
      <div class="empty-state">No membership history yet. Assign a plan from the Overview tab.</div>
    <?php else: ?>
    <table class="data">
      <thead><tr><th>Plan</th><th>Start</th><th>End</th><th>Status</th><th>Type</th></tr></thead>
      <tbody>
        <?php foreach ($membershipHistory as $m): $d = days_until($m['end_date']); ?>
          <tr>
            <td class="table-name"><?php echo h($m['plan_name']); ?> <span class="table-sub"><?php echo fmt_money($m['price']); ?></span></td>
            <td><?php echo fmt_date($m['start_date']); ?></td>
            <td><?php echo fmt_date($m['end_date']); ?></td>
            <td>
              <?php if ($m['status'] === 'cancelled'): ?>
                <span class="badge">Cancelled</span>
              <?php elseif ($d < 0): ?>
                <span class="badge badge--error">Expired</span>
              <?php elseif ($d <= 7): ?>
                <span class="badge badge--warn">Expiring soon</span>
              <?php else: ?>
                <span class="badge badge--success">Active</span>
              <?php endif; ?>
            </td>
            <td><?php echo $m['is_renewal'] ? 'Renewal' : 'New'; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

<?php elseif ($tab === 'payments'): ?>

  <div class="table-wrap reveal">
    <?php if (empty($paymentHistory)): ?>
      <div class="empty-state">No payments recorded yet.</div>
    <?php else: ?>
    <table class="data">
      <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th><th>Notes</th></tr></thead>
      <tbody>
        <?php foreach ($paymentHistory as $p): ?>
          <tr>
            <td><?php echo fmt_date($p['payment_date']); ?></td>
            <td class="table-name"><?php echo fmt_money($p['amount']); ?></td>
            <td><?php echo h(ucfirst(str_replace('_',' ',$p['payment_method']))); ?></td>
            <td><span class="badge <?php echo $p['status'] === 'paid' ? 'badge--success' : 'badge--warn'; ?>"><?php echo h(ucfirst($p['status'])); ?></span></td>
            <td class="table-sub"><?php echo h($p['notes'] ?: '—'); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

<?php elseif ($tab === 'attendance'): ?>

  <div class="table-wrap reveal">
    <?php if (empty($attendanceHistory)): ?>
      <div class="empty-state">No attendance recorded yet.</div>
    <?php else: ?>
    <table class="data">
      <thead><tr><th>Date</th><th>Check-in time</th></tr></thead>
      <tbody>
        <?php foreach ($attendanceHistory as $a): ?>
          <tr>
            <td class="table-name"><?php echo fmt_date($a['check_in_date']); ?></td>
            <td><?php echo h(date('g:i A', strtotime($a['check_in_time']))); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

<?php elseif ($tab === 'workout'): ?>

  <div class="panels" style="grid-template-columns:1fr 1.3fr;">
    <div class="panel-card reveal">
      <div class="panel-head"><h2>Assign a workout plan</h2></div>
      <form method="post">
        <input type="hidden" name="add_workout" value="1">
        <div class="field">
          <label for="wtitle">Title</label>
          <input class="input" type="text" id="wtitle" name="title" placeholder="e.g. Push / Pull / Legs" required>
        </div>
        <div class="field">
          <label for="wtrainer">Trainer</label>
          <select class="input" id="wtrainer" name="trainer_id">
            <option value="">Unassigned</option>
            <?php foreach ($trainers as $t): ?>
              <option value="<?php echo (int) $t['trainer_id']; ?>"><?php echo h($t['full_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="wdetails">Details</label>
          <textarea class="input" id="wdetails" name="details" placeholder="Exercises, sets/reps, notes…"></textarea>
        </div>
        <div class="form-actions">
          <button class="btn btn--primary" type="submit">Add workout plan</button>
        </div>
      </form>
    </div>

    <div class="panel-card reveal" style="animation-delay:0.05s">
      <div class="panel-head"><h2>Assigned workout plans</h2></div>
      <ul class="list">
        <?php if (empty($workoutPlans)): ?>
          <li class="list-empty">No workout plans assigned yet.</li>
        <?php else: foreach ($workoutPlans as $w): ?>
          <li class="list-row" style="align-items:flex-start;">
            <div class="list-main">
              <span class="list-name"><?php echo h($w['title']); ?></span>
              <span class="list-sub"><?php echo h($w['trainer_name'] ?? 'Unassigned'); ?> · <?php echo fmt_date($w['assigned_date']); ?></span>
              <?php if ($w['details']): ?><span class="list-sub"><?php echo nl2br(h($w['details'])); ?></span><?php endif; ?>
            </div>
          </li>
        <?php endforeach; endif; ?>
      </ul>
    </div>
  </div>

<?php endif; ?>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
