-- ---------------------------------------------------------------
-- New tables for the USER module (gym member side).
-- Run this once against the existing `mp_db` database
-- (phpMyAdmin -> mp_db -> SQL tab -> paste & go).
-- Safe to re-run: every statement is IF NOT EXISTS.
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
