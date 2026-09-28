-- ---------------------------------------------------------------
-- ADMIN MODULE + MEMBERSHIP NOTICES — Phase 4
-- Run this once against the existing `mp_db` database, after all
-- prior schema files.
-- (phpMyAdmin -> mp_db -> SQL tab -> paste & go)
-- ---------------------------------------------------------------

-- Platform-level admin accounts (separate from gym owners and users).
CREATE TABLE IF NOT EXISTS `admins` (
  `admin_id`   INT AUTO_INCREMENT PRIMARY KEY,
  `full_name`  VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- A default admin login for first access. CHANGE THIS PASSWORD after
-- your first login (Profile settings aren't built for admins yet —
-- for now, update it directly: UPDATE admins SET password='...' WHERE email='admin@fitco.com';)
INSERT IGNORE INTO `admins` (full_name, email, password)
  VALUES ('Platform Admin', 'admin@fitco.com', 'admin123');

-- Some installs' `gym` table predates created_at being tracked (it
-- wasn't in every version of gym_table_schema.sql). The admin
-- dashboard sorts/displays by it, so make sure it exists regardless
-- of how your `gym` table was originally created.
ALTER TABLE `gym`
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Gym approval workflow. Existing gyms (registered before this
-- migration) are backfilled as 'approved' so nobody already using
-- the platform gets locked out; every new signup starts 'pending'
-- until an admin reviews it (see fitCo/auth/verify-otp/verify-otp.php
-- and fitCo/admin/gyms/index.php).
ALTER TABLE `gym`
  ADD COLUMN IF NOT EXISTS `status` ENUM('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending' AFTER `gym_pass`;

UPDATE `gym` SET `status` = 'approved' WHERE `status` = 'pending';

-- Tracks whether the "expiring in 2 days" / "expired, not renewed"
-- emails have already gone out for a given membership period, so the
-- daily notice script (fitCo/cron/send-membership-notices.php) never
-- emails the same person twice for the same event.
ALTER TABLE `memberships`
  ADD COLUMN IF NOT EXISTS `expiry_reminder_sent_at` DATE NULL AFTER `previous_membership_id`,
  ADD COLUMN IF NOT EXISTS `expired_notice_sent_at`  DATE NULL AFTER `expiry_reminder_sent_at`;
