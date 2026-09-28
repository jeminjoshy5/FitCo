<?php
// Shared bootstrap for every page in the user (member self-service) module.
// Include this FIRST, before any output, from files that live one
// level down (fitCo/user/<module>/*.php) — paths below assume that.

session_start();
include(__DIR__ . "/../../assets/db.php");

// ---- Guard: only logged-in users get past this point ----
if (!isset($_SESSION['user'])) {
    header("Location: /mini-projectTEMP/fitCo/user-auth/login/login.php");
    exit();
}

$user_id = (int) $_SESSION['user']['user_id'];

$esc_id = (int) $user_id;
$userQuery  = "SELECT * FROM users WHERE user_id=$esc_id";
$userResult = mysqli_query($con, $userQuery);
if (!$userResult || mysqli_num_rows($userResult) === 0) {
    // Session refers to a user that no longer exists — bounce to login.
    session_unset();
    session_destroy();
    header("Location: /mini-projectTEMP/fitCo/user-auth/login/login.php");
    exit();
}
$user = mysqli_fetch_assoc($userResult);

if ($user['status'] === 'inactive') {
    session_unset();
    session_destroy();
    header("Location: /mini-projectTEMP/fitCo/user-auth/login/login.php?deactivated=1");
    exit();
}

$full_name = $user['full_name'];

// ---------------------------------------------------------------
// Linked gym membership (if the user has connected their account to
// a `members` row — see fitCo/user/link-membership/link.php).
// A user can be linked to at most one members row at a time.
// ---------------------------------------------------------------
$member    = null;
$member_id = null;
$gym       = null;
$gym_id    = null;

$memberQuery = "SELECT m.*, g.gym_name, g.gym_address, g.gym_mno, g.gym_alt_mno, g.gym_email
                FROM members m
                JOIN gym g ON g.gym_id = m.gym_id
                WHERE m.user_id=$esc_id
                LIMIT 1";
$memberResult = mysqli_query($con, $memberQuery);
if ($memberResult && mysqli_num_rows($memberResult) > 0) {
    $member    = mysqli_fetch_assoc($memberResult);
    $member_id = (int) $member['member_id'];
    $gym_id    = (int) $member['gym_id'];
    $gym       = [
        'gym_id'      => $gym_id,
        'gym_name'    => $member['gym_name'],
        'gym_address' => $member['gym_address'],
        'gym_mno'     => $member['gym_mno'],
        'gym_alt_mno' => $member['gym_alt_mno'],
        'gym_email'   => $member['gym_email'],
    ];
}

// Inline logout, same pattern as the rest of the app.
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: /mini-projectTEMP/fitCo/user-auth/login/login.php");
    exit();
}

// ---------------------------------------------------------------
// Small shared helpers used across the user module.
// (Mirrors fitCo/owner/includes/bootstrap.php so both modules feel
// consistent — kept as separate copies since the two modules are
// meant to stay independently deployable.)
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
