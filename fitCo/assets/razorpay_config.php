<?php
// ---------------------------------------------------------------
// Payment gateway — SIMULATOR MODE
//
// This project runs entirely on a local payment simulator (see
// razorpay.php) instead of the real Razorpay API. No account, API
// keys, or internet access are needed to test payments end-to-end —
// which is exactly what you want for a college project demo: it
// works the same on the projector with the wifi off as it does at
// your desk.
//
// PAYMENT_SIM_SECRET just needs to be a private string used to sign
// and verify simulated payments on the server (same idea as
// Razorpay's real signature check, just local instead of remote).
// Change it to any random string you like — it never leaves the
// server or reaches the browser.
// ---------------------------------------------------------------

define('PAYMENT_SIM_SECRET', 'fitco-local-simulator-secret-change-me');
