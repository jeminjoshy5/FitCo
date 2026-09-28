<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Members';
$activeNav = 'members';

$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';

$where = "me.gym_id = $gym_id";
if ($q !== '') {
    $qEsc = esc($con, $q);
    $where .= " AND (me.full_name LIKE '%$qEsc%' OR me.email LIKE '%$qEsc%' OR me.phone LIKE '%$qEsc%')";
}
if (in_array($status, ['active', 'inactive'], true)) {
    $where .= " AND me.status = '" . esc($con, $status) . "'";
}

$sql = "SELECT me.*, tr.full_name AS trainer_name,
        (SELECT mm.end_date FROM memberships mm WHERE mm.member_id = me.member_id
         ORDER BY mm.start_date DESC, mm.membership_id DESC LIMIT 1) AS current_end_date
        FROM members me
        LEFT JOIN trainers tr ON tr.trainer_id = me.trainer_id
        WHERE $where
        ORDER BY me.created_at DESC";
$result = mysqli_query($con, $sql);

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Members</h2>
    <p class="section-sub"><?php echo mysqli_num_rows($result); ?> result<?php echo mysqli_num_rows($result) == 1 ? '' : 's'; ?></p>
  </div>
  <a class="btn btn--primary" href="add.php">+ Register Member</a>
</div>

<div class="toolbar reveal" style="animation-delay:0.05s">
  <form method="get">
    <input class="input" type="text" name="q" placeholder="Search name, email, phone…" value="<?php echo h($q); ?>" style="min-width:240px">
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
    <div class="empty-state">No members match this search.</div>
  <?php else: ?>
  <table class="data">
    <thead>
      <tr>
        <th>Member</th>
        <th>Contact</th>
        <th>Trainer</th>
        <th>Membership ends</th>
        <th>Status</th>
        <th>Joined</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php while ($m = mysqli_fetch_assoc($result)): ?>
        <tr>
          <td><a class="table-name" href="view.php?id=<?php echo (int) $m['member_id']; ?>"><?php echo h($m['full_name']); ?></a></td>
          <td>
            <div><?php echo h($m['phone']); ?></div>
            <div class="table-sub"><?php echo h($m['email'] ?: '—'); ?></div>
          </td>
          <td><?php echo h($m['trainer_name'] ?? '—'); ?></td>
          <td><?php echo $m['current_end_date'] ? fmt_date($m['current_end_date']) : '—'; ?></td>
          <td><span class="badge <?php echo $m['status'] === 'active' ? 'badge--success' : ''; ?>"><?php echo h(ucfirst($m['status'])); ?></span></td>
          <td><?php echo fmt_date($m['joined_date']); ?></td>
          <td class="row-actions">
            <a href="view.php?id=<?php echo (int) $m['member_id']; ?>">View</a>
            <a href="edit.php?id=<?php echo (int) $m['member_id']; ?>">Edit</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
