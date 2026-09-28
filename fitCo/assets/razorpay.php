<?php
// ---------------------------------------------------------------
// Local payment gateway simulator.
//
// Mimics the two calls a real Razorpay integration makes — creating
// an order, then verifying a signature after payment — but both
// happen entirely on this server. No external API, no internet
// access, no API keys required. See simulate-payment.php for the
// step that stands in for the user completing checkout.
// ---------------------------------------------------------------

require_once __DIR__ . "/razorpay_config.php";

/**
 * Create a simulated order. Returns the same shape a real payment
 * gateway order would (id/amount/currency/receipt/status) so the
 * rest of the app doesn't need to know it's talking to a simulator.
 */
function razorpay_create_order($amountPaise, $receipt, $notes = []) {
    return [
        'id'       => 'order_sim_' . bin2hex(random_bytes(8)),
        'amount'   => (int) $amountPaise,
        'currency' => 'INR',
        'receipt'  => $receipt,
        'status'   => 'created',
        'notes'    => $notes,
    ];
}

/**
 * Generate a simulated successful payment for an order — stands in
 * for whatever a real gateway would send back after the user enters
 * card details and it charges successfully.
 *
 * Only ever called server-side (see simulate-payment.php), never
 * from the browser — that's what keeps this an honest test of the
 * signature-verification step below, instead of a rubber stamp.
 */
function razorpay_create_payment($orderId) {
    $paymentId = 'pay_sim_' . bin2hex(random_bytes(8));
    $signature = hash_hmac('sha256', $orderId . '|' . $paymentId, PAYMENT_SIM_SECRET);
    return [
        'payment_id' => $paymentId,
        'signature'  => $signature,
    ];
}

/**
 * Verify a payment's signature. Same principle as a real gateway
 * integration: never trust a client-reported "payment succeeded"
 * without recomputing this server-side first.
 */
function razorpay_verify_signature($orderId, $paymentId, $signature) {
    $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, PAYMENT_SIM_SECRET);
    return hash_equals($expected, (string) $signature);
}
