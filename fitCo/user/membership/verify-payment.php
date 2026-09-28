<?php
// See create-order.php — buffer away db.php's stray <script> echo so it
// can't corrupt this JSON response.
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
$order_id   = $input['razorpay_order_id'] ?? '';
$payment_id = $input['razorpay_payment_id'] ?? '';
$signature  = $input['razorpay_signature'] ?? '';

if (!$order_id || !$payment_id || !$signature) {
    echo json_encode(['success' => false, 'message' => 'Missing payment details.']);
    exit();
}

// Never trust the client-side callback alone — verify the signature.
if (!razorpay_verify_signature($order_id, $payment_id, $signature)) {
    error_log("Razorpay signature mismatch for order $order_id / payment $payment_id (user_id=$user_id)");
    echo json_encode(['success' => false, 'message' => 'Payment verification failed.']);
    exit();
}

// Recover the context we stashed in create-order.php.
$ctx = $_SESSION['pending_orders'][$order_id] ?? null;
if (!$ctx || (int) $ctx['member_id'] !== $member_id) {
    echo json_encode(['success' => false, 'message' => 'Could not match this payment to a pending order.']);
    exit();
}
unset($_SESSION['pending_orders'][$order_id]); // one-time use

// Guard against double-processing if the client retries this call.
$r = mysqli_query($con, "SELECT payment_id FROM payments WHERE notes LIKE '%$payment_id%' LIMIT 1");
if ($r && mysqli_num_rows($r) > 0) {
    echo json_encode(['success' => true]); // already recorded — treat as success
    exit();
}

$plan_id           = (int) $ctx['plan_id'];
$old_membership_id = $ctx['old_membership_id'] ? (int) $ctx['old_membership_id'] : null;

$r = mysqli_query($con, "SELECT * FROM membership_plans WHERE plan_id=$plan_id AND gym_id=$gym_id");
$plan = $r ? mysqli_fetch_assoc($r) : null;
if (!$plan) {
    echo json_encode(['success' => false, 'message' => 'Plan no longer exists — contact your gym, your payment was captured.']);
    exit();
}

// Suggested start: day after the old period ends if not yet expired, else today.
$start = date('Y-m-d');
if ($old_membership_id) {
    $r = mysqli_query($con, "SELECT end_date FROM memberships WHERE membership_id=$old_membership_id AND member_id=$member_id");
    $oldRow = $r ? mysqli_fetch_assoc($r) : null;
    if ($oldRow && strtotime($oldRow['end_date']) >= strtotime(date('Y-m-d'))) {
        $start = date('Y-m-d', strtotime($oldRow['end_date'] . ' +1 day'));
    }
}
$end = date('Y-m-d', strtotime("+{$plan['duration_days']} days", strtotime($start)));

mysqli_begin_transaction($con);
try {
    if ($old_membership_id) {
        mysqli_query($con, "UPDATE memberships SET status='expired' WHERE membership_id=$old_membership_id AND member_id=$member_id AND status != 'cancelled'");
    }

    $prevSql = $old_membership_id ? $old_membership_id : 'NULL';
    $isRenewal = $old_membership_id ? 1 : 0;
    mysqli_query($con, "INSERT INTO memberships (member_id, plan_id, start_date, end_date, status, is_renewal, previous_membership_id)
                         VALUES ($member_id, $plan_id, '$start', '$end', 'active', $isRenewal, $prevSql)");
    $newMembershipId = mysqli_insert_id($con);

    $amount = (float) $plan['price'];
    $notes  = esc($con, 'Razorpay payment ' . $payment_id . ' / order ' . $order_id);
    mysqli_query($con, "INSERT INTO payments (member_id, membership_id, amount, payment_method, status, payment_date, notes)
                         VALUES ($member_id, $newMembershipId, $amount, 'razorpay', 'paid', CURDATE(), '$notes')");

    mysqli_commit($con);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    mysqli_rollback($con);
    error_log("Renewal DB transaction failed after successful payment $payment_id: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment succeeded but we could not update your membership. Contact your gym with this payment ID: ' . $payment_id]);
}
