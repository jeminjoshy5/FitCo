-- Reconstructed from fitCo/auth/* and fitCo/owner/includes/bootstrap.php
-- (this table was referenced everywhere but its CREATE statement was
-- missing from the uploaded project — needed for the sandbox to run).
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
