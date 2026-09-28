<?php
// Shared bootstrap for every page in the platform-admin module.
// Include this FIRST, before any output, from files that live one
// level down (fitCo/admin/<module>/*.php) — paths below assume that.

session_start();
include(__DIR__ . "/../../assets/db.php");

// ---- Guard: only logged-in admins get past this point ----
if (!isset($_SESSION['admin'])) {
    header("Location: /mini-projectTEMP/fitCo/admin-auth/login/login.php");
    exit();
}

$admin_id   = (int) $_SESSION['admin']['admin_id'];
$admin_name = $_SESSION['admin']['full_name'] ?? 'Admin';

$esc_id = (int) $admin_id;
$adminQuery  = "SELECT * FROM admins WHERE admin_id=$esc_id";
$adminResult = mysqli_query($con, $adminQuery);
if (!$adminResult || mysqli_num_rows($adminResult) === 0) {
    session_unset();
    session_destroy();
    header("Location: /mini-projectTEMP/fitCo/admin-auth/login/login.php");
    exit();
}

// Inline logout, same pattern as the rest of the app.
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: /mini-projectTEMP/fitCo/admin-auth/login/login.php");
    exit();
}

// ---------------------------------------------------------------
// Small shared helpers — mirrors the owner/user bootstraps so all
// three modules feel consistent.
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

// ---------------------------------------------------------------
// Email a gym owner when their gym is approved (fresh approval or
// a reinstated suspended/rejected gym). Uses the same SMTP account
// as the rest of the project (see fitCo/cron/send-membership-notices.php).
// Failure to send never blocks the approval itself — it's a courtesy
// notice, not part of the approval transaction.
// ---------------------------------------------------------------
function send_gym_approval_email($toEmail, $ownerName, $gymName) {
    if (!$toEmail) return false;

    require_once __DIR__ . "/../../../vendor/autoload.php";

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'fitnwellnesscentre@gmail.com';
        $mail->Password   = 'tvyu ozff aqeu ttpe';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('fitnwellnesscentre@gmail.com', 'FITCO');
        $mail->addAddress($toEmail, $ownerName);

        $mail->isHTML(true);
        $mail->Subject = "Your gym has been approved on FITCO";
        $mail->Body    = "Hi " . htmlspecialchars($ownerName) . ",<br><br>"
                        . "Good news — <b>" . htmlspecialchars($gymName) . "</b> has been approved by a FITCO admin. "
                        . "You can now log in to your gym owner dashboard and start managing members, plans, and attendance.<br><br>"
                        . "— FITCO";
        $mail->AltBody = "Hi $ownerName, $gymName has been approved by a FITCO admin. You can now log in to your gym owner dashboard.";

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        error_log('Gym approval email failed: ' . $e->getMessage());
        return false;
    }
}
