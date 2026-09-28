<?php
// db.php (pulled in by bootstrap.php) echoes a stray <script> tag on every
// include — harmless on HTML pages, but it would corrupt a JSON response.
// Buffer it and throw it away before we send anything back.
ob_start();
require __DIR__ . "/../includes/bootstrap.php";
require __DIR__ . "/../../assets/razorpay.php";
ob_end_clean();

header('Content-Type: application/json');

if (!$member) {
    echo json_encode(['success' => false, 'message' => 'No membership linked.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$plan_id          = (int) ($input['plan_id'] ?? 0);
$old_membership_id = (int) ($input['membership_id'] ?? 0);

$r = mysqli_query($con, "SELECT * FROM membership_plans WHERE plan_id=$plan_id AND gym_id=$gym_id AND status='active'");
$plan = $r ? mysqli_fetch_assoc($r) : null;

if (!$plan) {
    echo json_encode(['success' => false, 'message' => 'That plan is no longer available.']);
    exit();
}

// If renewing, make sure the referenced membership really belongs to this
// member and is still their latest one (mirrors the owner-side guard).
if ($old_membership_id > 0) {
    $r = mysqli_query($con, "SELECT membership_id FROM memberships WHERE member_id=$member_id ORDER BY start_date DESC, membership_id DESC LIMIT 1");
    $latest = $r ? mysqli_fetch_assoc($r) : null;
    if (!$latest || (int) $latest['membership_id'] !== $old_membership_id) {
        echo json_encode(['success' => false, 'message' => 'This membership has already been renewed. Refresh the page and try again.']);
        exit();
    }
}

$amountPaise = (int) round($plan['price'] * 100);
$receipt = 'mem_' . $member_id . '_' . time();

$order = razorpay_create_order($amountPaise, $receipt, [
    'member_id'  => $member_id,
    'gym_id'     => $gym_id,
    'plan_id'    => $plan_id,
]);

// Bridge context between this step and simulate-payment.php/verify-payment.php —
// the "gateway" only gives back order/payment ids + a signature, not our own
// application data, so we keep it server-side keyed by order id.
$_SESSION['pending_orders'][$order['id']] = [
    'member_id'          => $member_id,
    'plan_id'             => $plan_id,
    'old_membership_id'   => $old_membership_id ?: null,
    'amount'              => $plan['price'],
];

echo json_encode([
    'success'  => true,
    'order_id' => $order['id'],
    'amount'   => $order['amount'],
    'currency' => $order['currency'],
]);
