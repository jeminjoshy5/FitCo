-- =================================================================
-- FITCO — FULL DATABASE SCHEMA (consolidated)
--
-- This is every table and column the project needs, combined from
-- the 6 incremental migration files in fitCo/assets/, in the correct
-- dependency order. Use this for a FRESH install instead of running
-- all 6 files separately.
--
-- If you've already run some or all of the individual files against
-- an existing `mp_db`, you can safely run this too — every statement
-- here is IF NOT EXISTS / INSERT IGNORE, so it won't duplicate or
-- error on anything that already exists.
--
-- Usage: create a database named `mp_db`, then run this file against
-- it (phpMyAdmin -> mp_db -> SQL tab -> paste & go, or:
--   mysql -u root mp_db < full_schema.sql
-- ).
--
-- Source files, in the order merged below:
--   1. gym_table_schema.sql
--   2. user_module_schema.sql
--   3. gym_module_schema.sql
--   4. user_module_phase1_schema.sql
--   5. user_module_phase3_schema.sql
--   6. admin_module_schema.sql
-- =================================================================


-- ---------------------------------------------------------------
-- 1. GYM TABLE
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gym` (
  `gym_id`       INT AUTO_INCREMENT PRIMARY KEY,
  `gym_name`     VARCHAR(150) NOT NULL,
  `owner_name`   VARCHAR(100) NOT NULL,
  `gym_email`    VARCHAR(150) NOT NULL UNIQUE,
  `gym_address`  TEXT NOT NULL,
  `gym_mno`      VARCHAR(15)  NOT NULL,
  `gym_alt_mno`  VARCHAR(15),
  `gym_license`  VARCHAR(255),
  `gym_pass`     VARCHAR(255) NOT NULL,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ---------------------------------------------------------------
-- 2. USER MODULE — base tables (member self-service side)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `user_id`    INT AUTO_INCREMENT PRIMARY KEY,
  `full_name`  VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `mobile`     VARCHAR(15)  NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `bmi_records` (
  `bmi_id`     INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `height`     DECIMAL(5,2) NOT NULL COMMENT 'cm',
  `weight`     DECIMAL(5,2) NOT NULL COMMENT 'kg',
  `bmi`        DECIMAL(4,1) NOT NULL,
  `category`   VARCHAR(30)  NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `user_splits` (
  `split_id`   INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `split_type` VARCHAR(30) NOT NULL COMMENT 'ppl | bro | custom',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `split_exercises` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `split_id`       INT NOT NULL,
  `body_part`      VARCHAR(40)  NOT NULL,
  `exercise_name`  VARCHAR(100) NOT NULL,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`split_id`) REFERENCES `user_splits`(`split_id`) ON DELETE CASCADE
);


-- ---------------------------------------------------------------
-- 3. GYM OWNER MODULE — dashboard, members, plans, renewals
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trainers` (
  `trainer_id`     INT AUTO_INCREMENT PRIMARY KEY,
  `gym_id`         INT NOT NULL,
  `full_name`      VARCHAR(100) NOT NULL,
  `email`          VARCHAR(150),
  `phone`          VARCHAR(15),
  `specialization` VARCHAR(100),
  `status`         ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`gym_id`) REFERENCES `gym`(`gym_id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `members` (
  `member_id`   INT AUTO_INCREMENT PRIMARY KEY,
  `gym_id`      INT NOT NULL,
  `full_name`   VARCHAR(100) NOT NULL,
  `email`       VARCHAR(150),
  `phone`       VARCHAR(15) NOT NULL,
  `gender`      ENUM('male','female','other') DEFAULT NULL,
  `dob`         DATE DEFAULT NULL,
  `address`     TEXT,
  `trainer_id`  INT DEFAULT NULL,
  `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `joined_date` DATE NOT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`gym_id`) REFERENCES `gym`(`gym_id`) ON DELETE CASCADE,
  FOREIGN KEY (`trainer_id`) REFERENCES `trainers`(`trainer_id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `membership_plans` (
  `plan_id`       INT AUTO_INCREMENT PRIMARY KEY,
  `gym_id`        INT NOT NULL,
  `plan_name`     VARCHAR(100) NOT NULL,
  `duration_days` INT NOT NULL,
  `price`         DECIMAL(10,2) NOT NULL,
  `description`   TEXT,
  `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`gym_id`) REFERENCES `gym`(`gym_id`) ON DELETE CASCADE
);

-- Every row here is one membership *period*. A renewal never edits an
-- old row — it closes it out and inserts a new one linked back via
-- previous_membership_id, so full history is preserved.
CREATE TABLE IF NOT EXISTS `memberships` (
  `membership_id`           INT AUTO_INCREMENT PRIMARY KEY,
  `member_id`                INT NOT NULL,
  `plan_id`                  INT NOT NULL,
  `start_date`                DATE NOT NULL,
  `end_date`                  DATE NOT NULL,
  `status`                    ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `is_renewal`                TINYINT(1) NOT NULL DEFAULT 0,
  `previous_membership_id`    INT DEFAULT NULL,
  `created_at`                 TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `membership_plans`(`plan_id`),
  FOREIGN KEY (`previous_membership_id`) REFERENCES `memberships`(`membership_id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id`      INT AUTO_INCREMENT PRIMARY KEY,
  `member_id`        INT NOT NULL,
  `membership_id`     INT DEFAULT NULL,
  `amount`             DECIMAL(10,2) NOT NULL,
  `payment_method`     VARCHAR(30) NOT NULL DEFAULT 'cash',
  `status`             ENUM('paid','pending') NOT NULL DEFAULT 'paid',
  `payment_date`        DATE NOT NULL,
  `notes`               VARCHAR(255),
  `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE CASCADE,
  FOREIGN KEY (`membership_id`) REFERENCES `memberships`(`membership_id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `attendance` (
  `attendance_id`   INT AUTO_INCREMENT PRIMARY KEY,
  `member_id`        INT NOT NULL,
  `gym_id`            INT NOT NULL,
  `check_in_date`      DATE NOT NULL,
  `check_in_time`       TIME NOT NULL,
  `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_member_day` (`member_id`, `check_in_date`),
  FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE CASCADE,
  FOREIGN KEY (`gym_id`) REFERENCES `gym`(`gym_id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `workout_plans` (
  `workout_plan_id`   INT AUTO_INCREMENT PRIMARY KEY,
  `member_id`           INT NOT NULL,
  `trainer_id`           INT DEFAULT NULL,
  `title`                 VARCHAR(150) NOT NULL,
  `details`                TEXT,
  `assigned_date`           DATE NOT NULL,
  `created_at`               TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE CASCADE,
  FOREIGN KEY (`trainer_id`) REFERENCES `trainers`(`trainer_id`) ON DELETE SET NULL
);


-- ---------------------------------------------------------------
-- 4. USER MODULE — Phase 1 (extra profile fields + gym linking)
-- ---------------------------------------------------------------
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `address`                 TEXT         NULL AFTER `mobile`,
  ADD COLUMN IF NOT EXISTS `profile_photo`            VARCHAR(255) NULL AFTER `address`,
  ADD COLUMN IF NOT EXISTS `emergency_contact_name`   VARCHAR(100) NULL AFTER `profile_photo`,
  ADD COLUMN IF NOT EXISTS `emergency_contact_phone`  VARCHAR(15)  NULL AFTER `emergency_contact_name`,
  ADD COLUMN IF NOT EXISTS `status`                   ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER `emergency_contact_phone`,
  ADD COLUMN IF NOT EXISTS `updated_at`                TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Link a `users` account to a gym's `members` roster row. A member is
-- added by the gym owner first; the user then "claims" that row from
-- their own account by matching gym + email. One user account can be
-- linked to at most one members row at a time.
ALTER TABLE `members`
  ADD COLUMN IF NOT EXISTS `user_id` INT NULL AFTER `member_id`;

-- These two error harmlessly if re-run and already exist (MySQL/MariaDB
-- versions before 10.5-ish don't support IF NOT EXISTS on ADD
-- CONSTRAINT/UNIQUE KEY) — that's expected and safe to ignore.
ALTER TABLE `members`
  ADD UNIQUE KEY `uniq_members_user_id` (`user_id`);

ALTER TABLE `members`
  ADD CONSTRAINT `fk_members_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL;


-- ---------------------------------------------------------------
-- 5. USER MODULE — Phase 3 (trainer change requests)
-- ---------------------------------------------------------------
-- A user's request to be assigned a different trainer. Submitted here;
-- the gym reviews it and makes the actual reassignment through the
-- owner module's member edit screen. (No owner-side review UI yet —
-- the request just sits here as 'pending' until the gym follows up.)
CREATE TABLE IF NOT EXISTS `trainer_change_requests` (
  `request_id`          INT AUTO_INCREMENT PRIMARY KEY,
  `member_id`            INT NOT NULL,
  `gym_id`                INT NOT NULL,
  `current_trainer_id`    INT NULL,
  `requested_trainer_id`  INT NULL,
  `note`                  TEXT NULL,
  `status`                ENUM('pending','reviewed') NOT NULL DEFAULT 'pending',
  `created_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE CASCADE,
  FOREIGN KEY (`gym_id`) REFERENCES `gym`(`gym_id`) ON DELETE CASCADE
);


-- ---------------------------------------------------------------
-- 6. ADMIN MODULE + MEMBERSHIP NOTICES
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
-- your first login (there's no admin profile page yet — for now,
-- update it directly:
--   UPDATE admins SET password='...' WHERE email='admin@fitco.com';
-- )
INSERT IGNORE INTO `admins` (full_name, email, password)
  VALUES ('Platform Admin', 'admin@fitco.com', 'admin123');

-- Some installs' `gym` table predates created_at being tracked. The
-- admin dashboard sorts/displays by it, so make sure it exists
-- regardless of how your `gym` table was originally created.
ALTER TABLE `gym`
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Gym approval workflow. Existing gyms are backfilled as 'approved' so
-- nobody already using the platform gets locked out; every new signup
-- starts 'pending' until an admin reviews it.
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

-- =================================================================
-- End of schema. 15 tables total:
-- gym, users, bmi_records, user_splits, split_exercises, trainers,
-- members, membership_plans, memberships, payments, attendance,
-- workout_plans, trainer_change_requests, admins
-- (14 listed — `gym` and `users` are the two root tables everything
-- else hangs off of.)
-- =================================================================
