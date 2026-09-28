-- ---------------------------------------------------------------
-- New tables for the GYM OWNER module (dashboard, member
-- management, membership management, renewals).
-- Run this once against the existing `mp_db` database, after
-- `user_module_schema.sql` and after the `gym` table exists.
-- Safe to re-run: every statement is IF NOT EXISTS.
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
