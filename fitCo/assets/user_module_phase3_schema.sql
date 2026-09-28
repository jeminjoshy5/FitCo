-- ---------------------------------------------------------------
-- USER MODULE — Phase 3 (Attendance + Trainer + Workout Plans)
-- Run this once against the existing `mp_db` database, after the
-- Phase 1 migration.
-- ---------------------------------------------------------------

-- A user's request to be assigned a different trainer. The user submits
-- it here; nothing changes automatically — the gym reviews it and makes
-- the actual reassignment through the owner module's existing member
-- edit screen. (No owner-side review UI yet — the request just sits
-- here as 'pending' until the gym follows up.)
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
