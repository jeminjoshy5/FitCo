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
$order_id = $input['order_id'] ?? '';

// Only ever "pay" an order this session actually created via create-order.php —
// stops someone from posting an arbitrary order id straight to this endpoint.
if (!$order_id || !isset($_SESSION['pending_orders'][$order_id])) {
    echo json_encode(['success' => false, 'message' => 'Unknown or expired order.']);
    exit();
}

// Simulate a brief processing delay, same feel as a real gateway.
usleep(400000);

$payment = razorpay_create_payment($order_id);

echo json_encode([
    'success'              => true,
    'razorpay_order_id'    => $order_id,
    'razorpay_payment_id'  => $payment['payment_id'],
    'razorpay_signature'   => $payment['signature'],
]);
