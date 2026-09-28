<?php
require __DIR__ . "/../includes/bootstrap.php";
$pageTitle = 'Attendance';
$activeNav = 'attendance';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_in'])) {
    $mid = (int) $_POST['member_id'];
    $owns = mysqli_fetch_assoc(mysqli_query($con, "SELECT member_id FROM members WHERE member_id=$mid AND gym_id=$gym_id"));
    if ($owns) {
        $exists = mysqli_fetch_assoc(mysqli_query($con, "SELECT attendance_id FROM attendance WHERE member_id=$mid AND check_in_date=CURDATE()"));
        if ($exists) {
            flash_set('error', 'Already checked in today.');
        } else {
            mysqli_query($con, "INSERT INTO attendance (member_id, gym_id, check_in_date, check_in_time) VALUES ($mid, $gym_id, CURDATE(), CURTIME())");
            flash_set('success', 'Checked in.');
        }
    }
    header("Location: index.php" . (isset($_GET['q']) ? '?q=' . urlencode($_GET['q']) : ''));
    exit();
}

$q = trim($_GET['q'] ?? '');
$searchResults = [];
if ($q !== '') {
    $qEsc = esc($con, $q);
    $r = mysqli_query($con, "SELECT me.*,
            (SELECT attendance_id FROM attendance a WHERE a.member_id = me.member_id AND a.check_in_date = CURDATE()) AS checked_in_today
            FROM members me WHERE me.gym_id=$gym_id AND me.status='active'
            AND (me.full_name LIKE '%$qEsc%' OR me.phone LIKE '%$qEsc%')
            ORDER BY me.full_name LIMIT 20");
    while ($row = mysqli_fetch_assoc($r)) { $searchResults[] = $row; }
}

$today = [];
$r = mysqli_query($con, "SELECT a.*, me.full_name, me.phone FROM attendance a
                          JOIN members me ON me.member_id = a.member_id
                          WHERE a.gym_id=$gym_id AND a.check_in_date=CURDATE()
                          ORDER BY a.check_in_time DESC");
while ($row = mysqli_fetch_assoc($r)) { $today[] = $row; }

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Attendance</h2>
    <p class="section-sub"><?php echo count($today); ?> checked in today.</p>
  </div>
</div>

<div class="panel-card reveal" style="max-width:640px; margin-bottom:24px;">
  <div class="panel-head"><h2>Mark attendance</h2></div>
  <form method="get">
    <input class="input" type="text" name="q" placeholder="Search member by name or phone…" value="<?php echo h($q); ?>" style="width:100%;">
  </form>
  <?php if ($q !== ''): ?>
    <ul class="list" style="margin-top:14px;">
      <?php if (empty($searchResults)): ?>
        <li class="list-empty">No active members match "<?php echo h($q); ?>".</li>
      <?php else: foreach ($searchResults as $m): ?>
        <li class="list-row">
          <div class="list-main">
            <span class="list-name"><?php echo h($m['full_name']); ?></span>
            <span class="list-sub"><?php echo h($m['phone']); ?></span>
          </div>
          <?php if ($m['checked_in_today']): ?>
            <span class="badge badge--success">Checked in</span>
          <?php else: ?>
            <form method="post">
              <input type="hidden" name="check_in" value="1">
              <input type="hidden" name="member_id" value="<?php echo (int) $m['member_id']; ?>">
              <button class="btn btn--primary btn--sm" type="submit">Mark present</button>
            </form>
          <?php endif; ?>
        </li>
      <?php endforeach; endif; ?>
    </ul>
  <?php endif; ?>
</div>

<div class="table-wrap reveal" style="animation-delay:0.05s">
  <div style="padding:16px 16px 0;"><h2 style="font-size:14px;font-weight:600;">Today's check-ins</h2></div>
  <div style="padding:12px 0;">
    <?php if (empty($today)): ?>
      <div class="empty-state">No check-ins yet today.</div>
    <?php else: ?>
    <table class="data">
      <thead><tr><th>Member</th><th>Phone</th><th>Time</th></tr></thead>
      <tbody>
        <?php foreach ($today as $a): ?>
          <tr>
            <td><a class="table-name" href="/mini-projectTEMP/fitCo/owner/members/view.php?id=<?php echo (int) $a['member_id']; ?>"><?php echo h($a['full_name']); ?></a></td>
            <td><?php echo h($a['phone']); ?></td>
            <td><?php echo h(date('g:i A', strtotime($a['check_in_time']))); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
