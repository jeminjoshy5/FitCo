<?php
require __DIR__ . "/../includes/bootstrap.php";
$pageTitle = 'Renew Membership';
$activeNav = 'renewals';

$oldId = (int) ($_GET['membership_id'] ?? $_POST['membership_id'] ?? 0);

$r = mysqli_query($con, "SELECT mm.*, mp.plan_name, mp.price AS plan_price, me.full_name, me.member_id
                          FROM memberships mm
                          JOIN membership_plans mp ON mp.plan_id = mm.plan_id
                          JOIN members me ON me.member_id = mm.member_id
                          WHERE mm.membership_id=$oldId AND me.gym_id=$gym_id");
$old = $r ? mysqli_fetch_assoc($r) : null;

if (!$old) {
    flash_set('error', 'Membership not found.');
    header("Location: index.php");
    exit();
}

// Make sure this is really the member's latest membership (renewals should
// chain off the current period, not an old superseded one).
$latest = mysqli_fetch_assoc(mysqli_query($con, "SELECT membership_id FROM memberships WHERE member_id={$old['member_id']}
                                                   ORDER BY start_date DESC, membership_id DESC LIMIT 1"));
if ((int) $latest['membership_id'] !== $oldId) {
    flash_set('error', 'This membership has already been renewed — showing the latest period instead.');
    header("Location: renew.php?membership_id=" . (int) $latest['membership_id']);
    exit();
}

$plans = [];
$rp = mysqli_query($con, "SELECT plan_id, plan_name, duration_days, price FROM membership_plans WHERE gym_id=$gym_id AND status='active' ORDER BY plan_name");
while ($row = mysqli_fetch_assoc($rp)) { $plans[] = $row; }

$errors = [];
$selected_plan_id = $old['plan_id'];
$payment_method = 'cash';
$payment_status = 'paid';

// Suggested new start: day after old end date if not yet expired, else today.
$suggestedStart = (strtotime($old['end_date']) >= strtotime(date('Y-m-d')))
    ? date('Y-m-d', strtotime($old['end_date'] . ' +1 day'))
    : date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['renew'])) {
    $selected_plan_id = (int) ($_POST['plan_id'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $payment_status = $_POST['payment_status'] ?? 'paid';
    $start_date = $_POST['start_date'] ?? $suggestedStart;

    $pr = mysqli_query($con, "SELECT * FROM membership_plans WHERE plan_id=$selected_plan_id AND gym_id=$gym_id AND status='active'");
    $plan = $pr ? mysqli_fetch_assoc($pr) : null;

    if (!$plan) {
        $errors[] = "Select a valid membership plan.";
    } elseif (!strtotime($start_date)) {
        $errors[] = "Enter a valid start date.";
    } else {
        $start = date('Y-m-d', strtotime($start_date));
        $end   = date('Y-m-d', strtotime("+{$plan['duration_days']} days", strtotime($start)));

        // 1. Close out the old membership period (row is kept, just marked expired).
        mysqli_query($con, "UPDATE memberships SET status='expired' WHERE membership_id=$oldId AND status != 'cancelled'");

        // 2. Insert the new membership period, linked back to the old one.
        mysqli_query($con, "INSERT INTO memberships (member_id, plan_id, start_date, end_date, status, is_renewal, previous_membership_id)
                             VALUES ({$old['member_id']}, {$plan['plan_id']}, '$start', '$end', 'active', 1, $oldId)");
        $newId = mysqli_insert_id($con);

        // 3. Record the renewal payment.
        $amount = (float) $plan['price'];
        $method = esc($con, $payment_method);
        $pstatus = in_array($payment_status, ['paid', 'pending'], true) ? $payment_status : 'paid';
        mysqli_query($con, "INSERT INTO payments (member_id, membership_id, amount, payment_method, status, payment_date, notes)
                             VALUES ({$old['member_id']}, $newId, $amount, '$method', '$pstatus', CURDATE(), 'Renewal')");

        flash_set('success', "{$old['full_name']}'s membership renewed — {$plan['plan_name']} until " . date('d M Y', strtotime($end)) . ".");
        header("Location: /mini-projectTEMP/fitCo/owner/members/view.php?id={$old['member_id']}&tab=membership");
        exit();
    }
}

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Renew membership — <?php echo h($old['full_name']); ?></h2>
    <p class="section-sub">
      Previous plan: <?php echo h($old['plan_name']); ?>, ended <?php echo fmt_date($old['end_date']); ?>.
      Renewing preserves this record and starts a new membership period.
    </p>
  </div>
  <a class="btn" href="/mini-projectTEMP/fitCo/owner/members/view.php?id=<?php echo (int) $old['member_id']; ?>&tab=membership">← Back to profile</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash--error reveal"><?php echo implode('<br>', array_map('h', $errors)); ?></div>
<?php endif; ?>

<form class="form-card reveal" method="post">
  <input type="hidden" name="renew" value="1">
  <input type="hidden" name="membership_id" value="<?php echo $oldId; ?>">

  <div class="field">
    <label for="plan_id">Membership plan</label>
    <select class="input" id="plan_id" name="plan_id" required>
      <?php foreach ($plans as $p): ?>
        <option value="<?php echo (int) $p['plan_id']; ?>" <?php echo (int) $selected_plan_id === (int) $p['plan_id'] ? 'selected' : ''; ?>>
          <?php echo h($p['plan_name']); ?> — <?php echo fmt_money($p['price']); ?> / <?php echo (int) $p['duration_days']; ?>d
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="field">
    <label for="start_date">New period starts</label>
    <input class="input" type="date" id="start_date" name="start_date" value="<?php echo h($suggestedStart); ?>">
    <span class="section-sub">Defaults to the day after the old period ends (or today, if already expired).</span>
  </div>

  <div class="field-row">
    <div class="field">
      <label for="payment_method">Payment method</label>
      <select class="input" id="payment_method" name="payment_method">
        <option value="cash">Cash</option>
        <option value="card">Card</option>
        <option value="upi">UPI</option>
        <option value="bank_transfer">Bank transfer</option>
      </select>
    </div>
    <div class="field">
      <label for="payment_status">Payment status</label>
      <select class="input" id="payment_status" name="payment_status">
        <option value="paid">Paid now</option>
        <option value="pending">Mark as pending</option>
      </select>
    </div>
  </div>

  <div class="form-actions">
    <button class="btn btn--primary" type="submit">Confirm renewal</button>
    <a class="btn" href="/mini-projectTEMP/fitCo/owner/members/view.php?id=<?php echo (int) $old['member_id']; ?>">Cancel</a>
  </div>
</form>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
