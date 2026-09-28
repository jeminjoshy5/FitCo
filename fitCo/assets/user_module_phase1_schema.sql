-- ---------------------------------------------------------------
-- USER MODULE — Phase 1 (Linking + Dashboard + Profile)
-- Run this once against the existing `mp_db` database, after
-- user_module_schema.sql and gym_module_schema.sql.
-- (phpMyAdmin -> mp_db -> SQL tab -> paste & go)
-- ---------------------------------------------------------------

-- Extra profile fields for the self-service `users` table.
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `address`                 TEXT         NULL AFTER `mobile`,
  ADD COLUMN IF NOT EXISTS `profile_photo`            VARCHAR(255) NULL AFTER `address`,
  ADD COLUMN IF NOT EXISTS `emergency_contact_name`   VARCHAR(100) NULL AFTER `profile_photo`,
  ADD COLUMN IF NOT EXISTS `emergency_contact_phone`  VARCHAR(15)  NULL AFTER `emergency_contact_name`,
  ADD COLUMN IF NOT EXISTS `status`                   ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER `emergency_contact_phone`,
  ADD COLUMN IF NOT EXISTS `updated_at`                TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Link a `users` account to a gym's `members` roster row.
-- A member is added by the gym owner first (name, email, phone, etc.);
-- the user then "claims" that row from their own account by matching
-- gym + email (see fitCo/user/link-membership/link.php). One user
-- account can be linked to at most one members row at a time.
ALTER TABLE `members`
  ADD COLUMN IF NOT EXISTS `user_id` INT NULL AFTER `member_id`;

-- These two can only run once each — re-running the file will error
-- here (harmlessly) if they already exist; that's expected on a re-run.
ALTER TABLE `members`
  ADD UNIQUE KEY `uniq_members_user_id` (`user_id`);

ALTER TABLE `members`
  ADD CONSTRAINT `fk_members_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL;
