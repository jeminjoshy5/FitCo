<?php
// ---------------------------------------------------------------
// Membership expiry notice script.
//
// Run this once a day OUTSIDE the browser — via Windows Task
// Scheduler (XAMPP) or a cron job (Linux) — not as a page a user
// visits. It has no session/auth guard because it isn't meant to be
// reachable over HTTP; nothing in this project links to it.
//
// What it does, each run:
//   1. Emails anyone whose active membership expires in exactly 2
//      days (once per membership — tracked via
//      memberships.expiry_reminder_sent_at).
//   2. Emails anyone whose membership's end_date has already passed
//      and was never renewed, marks that membership 'expired', and
//      sends the notice once (tracked via
//      memberships.expired_notice_sent_at).
//
// Windows Task Scheduler example (daily, e.g. 8:00 AM):
//   Program/script:  C:\xampp\php\php.exe
//   Arguments:       C:\xampp\htdocs\mini-projectTEMP\fitCo\cron\send-membership-notices.php
//
// Linux/macOS cron example (daily at 8:00 AM):
//   0 8 * * * /usr/bin/php /path/to/mini-projectTEMP/fitCo/cron/send-membership-notices.php
// ---------------------------------------------------------------

// db.php echoes a stray <script> tag on every include — harmless on
// HTML pages, just noise on the CLI, so swallow it here.
ob_start();
require __DIR__ . "/../assets/db.php";
ob_end_clean();

require __DIR__ . "/../../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Same SMTP account used for OTP emails elsewhere in this project
// (see fitCo/auth/verify-otp/verify-otp.php and
// fitCo/user-auth/verify-otp/verify-otp.php).
function send_notice_email($toEmail, $toName, $subject, $bodyHtml, $bodyText) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'fitnwellnesscentre@gmail.com';
        $mail->Password   = 'tvyu ozff aqeu ttpe';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('fitnwellnesscentre@gmail.com', 'FITCO');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = $bodyText;

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "  Mail error for $toEmail: " . $mail->ErrorInfo . PHP_EOL;
        return false;
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Running membership notice check...\n";

// ---------------------------------------------------------------
// 1. Expiring in exactly 2 days — reminder email.
// ---------------------------------------------------------------
$sql = "SELECT mm.membership_id, mm.end_date,
               m.full_name AS member_name, m.email AS member_email, m.user_id,
               u.email AS user_email, u.full_name AS user_full_name,
               g.gym_name, mp.plan_name
        FROM memberships mm
        JOIN members m ON m.member_id = mm.member_id
        LEFT JOIN users u ON u.user_id = m.user_id
        JOIN gym g ON g.gym_id = m.gym_id
        JOIN membership_plans mp ON mp.plan_id = mm.plan_id
        WHERE mm.status = 'active'
          AND mm.end_date = DATE_ADD(CURDATE(), INTERVAL 2 DAY)
          AND mm.expiry_reminder_sent_at IS NULL";
$result = mysqli_query($con, $sql);
$reminderCount = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $toEmail = $row['user_email'] ?: $row['member_email'];
    $toName  = $row['user_full_name'] ?: $row['member_name'];
    if (!$toEmail) {
        echo "  Skipping membership #{$row['membership_id']} — no email on file.\n";
        continue;
    }

    $endDate = date('d M Y', strtotime($row['end_date']));
    $subject = "Your {$row['gym_name']} membership expires in 2 days";
    $bodyHtml = "Hi " . htmlspecialchars($toName) . ",<br><br>"
              . "Your <b>" . htmlspecialchars($row['plan_name']) . "</b> membership at "
              . "<b>" . htmlspecialchars($row['gym_name']) . "</b> expires on <b>$endDate</b>.<br><br>"
              . "Log in to your FITCO account to renew and keep your access uninterrupted.<br><br>— FITCO";
    $bodyText = "Hi $toName, your {$row['plan_name']} membership at {$row['gym_name']} expires on $endDate. "
              . "Log in to your FITCO account to renew.";

    if (send_notice_email($toEmail, $toName, $subject, $bodyHtml, $bodyText)) {
        mysqli_query($con, "UPDATE memberships SET expiry_reminder_sent_at = CURDATE() WHERE membership_id = " . (int) $row['membership_id']);
        $reminderCount++;
        echo "  Reminder sent to $toEmail (membership #{$row['membership_id']}).\n";
    }
}
echo "  $reminderCount reminder email(s) sent.\n";

// ---------------------------------------------------------------
// 2. Already expired, never renewed — mark expired + notify once.
// ---------------------------------------------------------------
$sql = "SELECT mm.membership_id, mm.end_date,
               m.full_name AS member_name, m.email AS member_email, m.user_id,
               u.email AS user_email, u.full_name AS user_full_name,
               g.gym_name, mp.plan_name
        FROM memberships mm
        JOIN members m ON m.member_id = mm.member_id
        LEFT JOIN users u ON u.user_id = m.user_id
        JOIN gym g ON g.gym_id = m.gym_id
        JOIN membership_plans mp ON mp.plan_id = mm.plan_id
        WHERE mm.status != 'cancelled'
          AND mm.end_date < CURDATE()
          AND mm.expired_notice_sent_at IS NULL
          AND mm.membership_id = (
              SELECT mm2.membership_id FROM memberships mm2
              WHERE mm2.member_id = mm.member_id
              ORDER BY mm2.start_date DESC, mm2.membership_id DESC LIMIT 1
          )";
$result = mysqli_query($con, $sql);
$expiredCount = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $membershipId = (int) $row['membership_id'];
    $toEmail = $row['user_email'] ?: $row['member_email'];
    $toName  = $row['user_full_name'] ?: $row['member_name'];

    // Mark expired either way — the period is genuinely over even if
    // there's no address to notify.
    mysqli_query($con, "UPDATE memberships SET status='expired' WHERE membership_id=$membershipId AND status='active'");

    if (!$toEmail) {
        echo "  Marked membership #$membershipId expired — no email on file to notify.\n";
        continue;
    }

    $endDate = date('d M Y', strtotime($row['end_date']));
    $subject = "Your {$row['gym_name']} membership has expired";
    $bodyHtml = "Hi " . htmlspecialchars($toName) . ",<br><br>"
              . "Your <b>" . htmlspecialchars($row['plan_name']) . "</b> membership at "
              . "<b>" . htmlspecialchars($row['gym_name']) . "</b> expired on <b>$endDate</b> and hasn't been renewed.<br><br>"
              . "Log in to your FITCO account any time to start a new period.<br><br>— FITCO";
    $bodyText = "Hi $toName, your {$row['plan_name']} membership at {$row['gym_name']} expired on $endDate and hasn't "
              . "been renewed. Log in to your FITCO account to start a new period.";

    if (send_notice_email($toEmail, $toName, $subject, $bodyHtml, $bodyText)) {
        mysqli_query($con, "UPDATE memberships SET expired_notice_sent_at = CURDATE() WHERE membership_id = $membershipId");
        $expiredCount++;
        echo "  Expiry notice sent to $toEmail (membership #$membershipId).\n";
    }
}
echo "  $expiredCount expiry notice(s) sent.\n";
echo "Done.\n";
