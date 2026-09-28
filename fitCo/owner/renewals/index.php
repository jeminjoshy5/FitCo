<?php
require __DIR__ . "/../includes/bootstrap.php";
$pageTitle = 'Renewals';
$activeNav = 'renewals';

$sql = "SELECT mm.*, mp.plan_name, mp.price, me.full_name, me.phone
        FROM memberships mm
        JOIN membership_plans mp ON mp.plan_id = mm.plan_id
        JOIN members me ON me.member_id = mm.member_id
        WHERE me.gym_id = $gym_id AND mm.status != 'cancelled'
        AND mm.membership_id = (
            SELECT mm2.membership_id FROM memberships mm2
            WHERE mm2.member_id = mm.member_id
            ORDER BY mm2.start_date DESC, mm2.membership_id DESC LIMIT 1
        )
        ORDER BY mm.end_date ASC";
$r = mysqli_query($con, $sql);

$expired = []; $expiring = [];
while ($row = mysqli_fetch_assoc($r)) {
    $d = days_until($row['end_date']);
    $row['days_left'] = $d;
    if ($d < 0) $expired[] = $row;
    elseif ($d <= 7) $expiring[] = $row;
}

require __DIR__ . "/../includes/layout-top.php";

function renewal_table($rows, $emptyMsg) {
    if (empty($rows)) { echo '<div class="empty-state">' . h($emptyMsg) . '</div>'; return; }
    echo '<table class="data"><thead><tr><th>Member</th><th>Plan</th><th>Ended</th><th>Status</th><th></th></tr></thead><tbody>';
    foreach ($rows as $m) {
        $d = $m['days_left'];
        echo '<tr>';
        echo '<td><a class="table-name" href="/mini-projectTEMP/fitCo/owner/members/view.php?id=' . (int)$m['member_id'] . '">' . h($m['full_name']) . '</a><div class="table-sub">' . h($m['phone']) . '</div></td>';
        echo '<td>' . h($m['plan_name']) . ' <span class="table-sub">' . fmt_money($m['price']) . '</span></td>';
        echo '<td>' . fmt_date($m['end_date']) . '</td>';
        if ($d < 0) {
            echo '<td><span class="badge badge--error">Expired ' . abs($d) . 'd ago</span></td>';
        } else {
            echo '<td><span class="badge badge--warn">' . $d . 'd left</span></td>';
        }
        echo '<td class="row-actions"><a href="renew.php?membership_id=' . (int)$m['membership_id'] . '">Renew</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
?>

<div class="section-head reveal">
  <div>
    <h2>Renewals</h2>
    <p class="section-sub">Memberships that need attention — already expired, or expiring within 7 days.</p>
  </div>
</div>

<div class="panels" style="grid-template-columns:1fr;">
  <div class="table-wrap reveal">
    <div style="padding:16px 16px 0;"><h2 style="font-size:14px;font-weight:600;">Already expired (<?php echo count($expired); ?>)</h2></div>
    <div style="padding:12px 0;"><?php renewal_table($expired, 'No expired memberships. Nice.'); ?></div>
  </div>

  <div class="table-wrap reveal" style="animation-delay:0.05s">
    <div style="padding:16px 16px 0;"><h2 style="font-size:14px;font-weight:600;">Expiring within 7 days (<?php echo count($expiring); ?>)</h2></div>
    <div style="padding:12px 0;"><?php renewal_table($expiring, 'Nothing expiring soon.'); ?></div>
  </div>
</div>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
