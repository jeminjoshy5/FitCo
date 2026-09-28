<?php
// Shared bootstrap for every page in the gym-owner module.
// Include this FIRST, before any output, from files that live one
// level down (fitCo/owner/<module>/*.php) — paths below assume that.

session_start();
include(__DIR__ . "/../../assets/db.php");

// ---- Guard: only logged-in gym owners get past this point ----
if (!isset($_SESSION['login'])) {
    header("Location: /mini-projectTEMP/fitCo/auth/login/login.php");
    exit();
}

$login_email = $_SESSION['login']['login_email'];

$gym_id     = null;
$gym_name   = "Your Gym";
$owner_name = "";

$esc_email = mysqli_real_escape_string($con, $login_email);
$gymQuery  = "SELECT * FROM gym WHERE gym_email='$esc_email'";
$gymResult = mysqli_query($con, $gymQuery);
if ($gymResult && mysqli_num_rows($gymResult) > 0) {
    $gym        = mysqli_fetch_assoc($gymResult);
    $gym_id     = (int) $gym['gym_id'];
    $gym_name   = $gym['gym_name']   ?? $gym_name;
    $owner_name = $gym['owner_name'] ?? '';

    if ($gym['status'] !== 'approved') {
        session_unset();
        session_destroy();
        header("Location: /mini-projectTEMP/fitCo/auth/login/login.php");
        exit();
    }
} else {
    // Session refers to a gym that no longer exists — bounce to login.
    session_unset();
    session_destroy();
    header("Location: /mini-projectTEMP/fitCo/auth/login/login.php");
    exit();
}

// Inline logout, same pattern as the rest of the app.
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: /mini-projectTEMP/fitCo/auth/login/login.php");
    exit();
}

// ---------------------------------------------------------------
// Small shared helpers used across the owner module.
// ---------------------------------------------------------------

function esc($con, $v) {
    return mysqli_real_escape_string($con, trim((string) $v));
}

function h($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES);
}

function fmt_money($n) {
    return '₹' . number_format((float) $n, 2);
}

function fmt_date($d) {
    if (!$d) return '—';
    $t = strtotime($d);
    return $t ? date('d M Y', $t) : '—';
}

function days_until($date) {
    $today = new DateTime(date('Y-m-d'));
    $target = new DateTime(date('Y-m-d', strtotime($date)));
    return (int) $today->diff($target)->format('%r%a');
}

// Flash messages via session (survive a redirect after POST).
function flash_set($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function flash_get() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
