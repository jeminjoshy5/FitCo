<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Users';
$activeNav = 'users';

// ---- Handle status-change actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $targetId = (int) $_POST['user_id'];
    $newStatus = $_POST['action'] === 'suspend' ? 'inactive' : 'active';
    if ($targetId > 0) {
        mysqli_query($con, "UPDATE users SET status='$newStatus' WHERE user_id=$targetId");
        flash_set('success', 'User status updated.');
    }
    header("Location: index.php" . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit();
}

$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';

$where = "1=1";
if ($q !== '') {
    $qEsc = esc($con, $q);
    $where .= " AND (full_name LIKE '%$qEsc%' OR email LIKE '%$qEsc%' OR mobile LIKE '%$qEsc%')";
}
if (in_array($status, ['active', 'inactive'], true)) {
    $where .= " AND status = '" . esc($con, $status) . "'";
}

$sql = "SELECT u.*,
        (SELECT g.gym_name FROM members m JOIN gym g ON g.gym_id = m.gym_id WHERE m.user_id = u.user_id LIMIT 1) AS linked_gym
        FROM users u WHERE $where ORDER BY u.created_at DESC";
$result = mysqli_query($con, $sql);

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Users</h2>
    <p class="section-sub"><?php echo mysqli_num_rows($result); ?> result<?php echo mysqli_num_rows($result) == 1 ? '' : 's'; ?></p>
  </div>
</div>

<div class="toolbar reveal" style="animation-delay:0.05s">
  <form method="get">
    <input class="input" type="text" name="q" placeholder="Search name, email, mobile…" value="<?php echo h($q); ?>" style="min-width:240px">
    <select class="input" name="status" onchange="this.form.submit()">
      <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All statuses</option>
      <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
      <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
    </select>
    <button class="btn" type="submit">Filter</button>
    <?php if ($q !== '' || $status !== 'all'): ?>
      <a class="btn" href="index.php">Clear</a>
    <?php endif; ?>
  </form>
</div>

<div class="table-wrap reveal" style="animation-delay:0.1s">
  <?php if (mysqli_num_rows($result) === 0): ?>
    <div class="empty-state">No users match this search.</div>
  <?php else: ?>
  <table class="data">
    <thead>
      <tr>
        <th>User</th>
        <th>Contact</th>
        <th>Linked gym</th>
        <th>Status</th>
        <th>Joined</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php while ($u = mysqli_fetch_assoc($result)): ?>
        <tr>
          <td><span class="table-name"><?php echo h($u['full_name']); ?></span></td>
          <td>
            <div><?php echo h($u['email']); ?></div>
            <div class="table-sub"><?php echo h($u['mobile']); ?></div>
          </td>
          <td><?php echo h($u['linked_gym'] ?? '—'); ?></td>
          <td><span class="badge <?php echo $u['status'] === 'active' ? 'badge--success' : 'badge--error'; ?>"><?php echo h(ucfirst($u['status'])); ?></span></td>
          <td><?php echo fmt_date($u['created_at']); ?></td>
          <td class="row-actions">
            <form method="post" style="display:inline;" onsubmit="return confirm('<?php echo $u['status'] === 'active' ? 'Suspend' : 'Reactivate'; ?> this account?');">
              <input type="hidden" name="user_id" value="<?php echo (int) $u['user_id']; ?>">
              <input type="hidden" name="action" value="<?php echo $u['status'] === 'active' ? 'suspend' : 'reactivate'; ?>">
              <button type="submit" class="link-btn"><?php echo $u['status'] === 'active' ? 'Suspend' : 'Reactivate'; ?></button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
