<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Attendance';
$activeNav = 'attendance';

if (!$member) {
    header("Location: /mini-projectTEMP/fitCo/user/link-membership/link.php");
    exit();
}

// Date-range filter (defaults to this calendar month).
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
if (!strtotime($from)) $from = date('Y-m-01');
if (!strtotime($to))   $to   = date('Y-m-d');
$fromEsc = esc($con, $from);
$toEsc   = esc($con, $to);

$today = date('Y-m-d');
$r = mysqli_query($con, "SELECT 1 FROM attendance WHERE member_id=$member_id AND check_in_date='$today' LIMIT 1");
$todayCheckedIn = $r && mysqli_num_rows($r) > 0;

$r = mysqli_query($con, "SELECT COUNT(*) c FROM attendance WHERE member_id=$member_id AND check_in_date >= '" . date('Y-m-01') . "'");
$monthCount = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);

$r = mysqli_query($con, "SELECT COUNT(*) c FROM attendance WHERE member_id=$member_id");
$totalCount = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);

$r = mysqli_query($con, "SELECT COUNT(*) c FROM attendance WHERE member_id=$member_id AND check_in_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$last30Count = (int) (mysqli_fetch_assoc($r)['c'] ?? 0);

$rows = [];
$r = mysqli_query($con, "SELECT * FROM attendance WHERE member_id=$member_id AND check_in_date BETWEEN '$fromEsc' AND '$toEsc' ORDER BY check_in_date DESC, check_in_time DESC");
while ($row = mysqli_fetch_assoc($r)) { $rows[] = $row; }

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Attendance</h2>
    <p class="section-sub">Check-ins are recorded by the front desk when you arrive at <?php echo h($gym['gym_name']); ?>.</p>
  </div>
</div>

<section class="stats-grid">
  <div class="stat-card reveal" style="animation-delay:0.03s">
    <span class="stat-label">Today</span>
    <span class="stat-value <?php echo $todayCheckedIn ? 'stat-value--success' : ''; ?>"><?php echo $todayCheckedIn ? 'Checked in' : 'Not yet'; ?></span>
    <span class="stat-sub">as of <?php echo date('g:i A'); ?></span>
  </div>
  <div class="stat-card reveal" style="animation-delay:0.06s">
    <span class="stat-label">This Month</span>
    <span class="stat-value"><?php echo $monthCount; ?></span>
    <span class="stat-sub">check-ins in <?php echo date('F'); ?></span>
  </div>
  <div class="stat-card reveal" style="animation-delay:0.09s">
    <span class="stat-label">Last 30 Days</span>
    <span class="stat-value"><?php echo $last30Count; ?></span>
    <span class="stat-sub">rolling window</span>
  </div>
  <div class="stat-card reveal" style="animation-delay:0.12s">
    <span class="stat-label">All Time</span>
    <span class="stat-value"><?php echo $totalCount; ?></span>
    <span class="stat-sub">total check-ins</span>
  </div>
</section>

<div class="panel-card reveal" style="animation-delay:0.16s; margin-bottom:20px;">
  <div class="panel-head"><h2>Filter history</h2></div>
  <form class="field-row" method="get" style="align-items:flex-end;">
    <div class="field">
      <label for="from">From</label>
      <input class="input" type="date" id="from" name="from" value="<?php echo h($from); ?>">
    </div>
    <div class="field">
      <label for="to">To</label>
      <input class="input" type="date" id="to" name="to" value="<?php echo h($to); ?>">
    </div>
    <div class="field">
      <button class="btn btn--primary" type="submit">Apply</button>
    </div>
  </form>
</div>

<div class="table-wrap reveal">
  <?php if (empty($rows)): ?>
    <div class="empty-state">No check-ins in this date range.</div>
  <?php else: ?>
    <table class="data">
      <thead>
        <tr><th>Date</th><th>Time</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $a): ?>
          <tr>
            <td class="table-name"><?php echo fmt_date($a['check_in_date']); ?></td>
            <td><?php echo date('g:i A', strtotime($a['check_in_time'])); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
