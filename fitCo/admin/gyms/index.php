<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Gyms';
$activeNav = 'gyms';

// ---- Handle status-change actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gym_id'], $_POST['action'])) {
    $targetId = (int) $_POST['gym_id'];
    $action   = $_POST['action'];
    $map = [
        'approve'   => 'approved',
        'reject'    => 'rejected',
        'suspend'   => 'suspended',
        'reinstate' => 'approved',
    ];
    if (isset($map[$action]) && $targetId > 0) {
        mysqli_query($con, "UPDATE gym SET status='" . $map[$action] . "' WHERE gym_id=$targetId");

        if ($map[$action] === 'approved') {
            $g = mysqli_fetch_assoc(mysqli_query($con, "SELECT gym_email, owner_name, gym_name FROM gym WHERE gym_id=$targetId"));
            if ($g) {
                send_gym_approval_email($g['gym_email'], $g['owner_name'], $g['gym_name']);
            }
        }

        flash_set('success', 'Gym status updated.');
    }
    header("Location: index.php" . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit();
}

$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';

$where = "1=1";
if ($q !== '') {
    $qEsc = esc($con, $q);
    $where .= " AND (gym_name LIKE '%$qEsc%' OR owner_name LIKE '%$qEsc%' OR gym_email LIKE '%$qEsc%')";
}
if (in_array($status, ['pending', 'approved', 'rejected', 'suspended'], true)) {
    $where .= " AND status = '" . esc($con, $status) . "'";
}

$result = mysqli_query($con, "SELECT * FROM gym WHERE $where ORDER BY created_at DESC");

require __DIR__ . "/../includes/layout-top.php";

$badgeClass = [
    'pending'   => 'badge--warn',
    'approved'  => 'badge--success',
    'rejected'  => 'badge--error',
    'suspended' => 'badge--error',
];
?>

<div class="section-head reveal">
  <div>
    <h2>Gyms</h2>
    <p class="section-sub"><?php echo mysqli_num_rows($result); ?> result<?php echo mysqli_num_rows($result) == 1 ? '' : 's'; ?></p>
  </div>
</div>

<div class="toolbar reveal" style="animation-delay:0.05s">
  <form method="get">
    <input class="input" type="text" name="q" placeholder="Search gym, owner, email…" value="<?php echo h($q); ?>" style="min-width:240px">
    <select class="input" name="status" onchange="this.form.submit()">
      <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All statuses</option>
      <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
      <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
      <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
      <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
    </select>
    <button class="btn" type="submit">Filter</button>
    <?php if ($q !== '' || $status !== 'all'): ?>
      <a class="btn" href="index.php">Clear</a>
    <?php endif; ?>
  </form>
</div>

<div class="table-wrap reveal" style="animation-delay:0.1s">
  <?php if (mysqli_num_rows($result) === 0): ?>
    <div class="empty-state">No gyms match this search.</div>
  <?php else: ?>
  <table class="data">
    <thead>
      <tr>
        <th>Gym</th>
        <th>Owner</th>
        <th>Contact</th>
        <th>Status</th>
        <th>Registered</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php while ($g = mysqli_fetch_assoc($result)): ?>
        <tr>
          <td><a class="table-name" href="view.php?id=<?php echo (int) $g['gym_id']; ?>"><?php echo h($g['gym_name']); ?></a></td>
          <td><?php echo h($g['owner_name']); ?></td>
          <td>
            <div><?php echo h($g['gym_email']); ?></div>
            <div class="table-sub"><?php echo h($g['gym_mno']); ?></div>
          </td>
          <td><span class="badge <?php echo $badgeClass[$g['status']] ?? ''; ?>"><?php echo h(ucfirst($g['status'])); ?></span></td>
          <td><?php echo fmt_date($g['created_at']); ?></td>
          <td class="row-actions">
            <a href="view.php?id=<?php echo (int) $g['gym_id']; ?>">Review</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
