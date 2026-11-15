-- =====================================================================
--  E-Shongskar : Community Reform Platform
--  Database   : e_shongskar
--  Target     : MariaDB 10.4+ / MySQL 5.7+ (XAMPP)
--
--  How to use : open phpMyAdmin -> SQL tab -> paste this whole file -> Go
--               (or: Import -> choose this file -> Go)
--
--  Default admin login (change the password after first login):
--      username : admin
--      password : admin123
-- =====================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+06:00";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS `e_shongskar`;
CREATE DATABASE `e_shongskar` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `e_shongskar`;

-- ---------------------------------------------------------------------
--  1. Accounts
-- ---------------------------------------------------------------------

CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `full_name`     VARCHAR(100) NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `phone`         VARCHAR(20)  NOT NULL,
  `nid`           VARCHAR(30)  NOT NULL COMMENT 'NID or birth certificate number',
  `dob`           DATE         NOT NULL,
  `gender`        ENUM('Male','Female','Other') NOT NULL,
  `blood_group`   ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-','Unknown') NOT NULL DEFAULT 'Unknown',
  `occupation`    VARCHAR(100) DEFAULT NULL,
  `division`      ENUM('Dhaka','Chattogram','Rajshahi','Khulna','Barishal','Sylhet','Rangpur','Mymensingh') NOT NULL,
  `area`          VARCHAR(100) NOT NULL COMMENT 'Neighbourhood / thana / upazila. Users in the same area form one community.',
  `street`        VARCHAR(150) DEFAULT NULL,
  `house_no`      VARCHAR(50)  DEFAULT NULL,
  `avatar`        VARCHAR(255) DEFAULT NULL,
  `bio`           VARCHAR(500) DEFAULT NULL,
  `is_verified`   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = verified local representative',
  `verified_at`   DATETIME     DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `last_seen`     DATETIME     DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_nid` (`nid`),
  KEY `idx_users_area` (`area`),
  KEY `idx_users_last_seen` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `admins` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `full_name`     VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `user_id`       INT UNSIGNED DEFAULT NULL COMMENT 'Set when a citizen account was promoted to admin',
  `last_login`    DATETIME     DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`),
  UNIQUE KEY `uq_admins_user` (`user_id`),
  CONSTRAINT `fk_admins_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `social_links` (
  `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`  INT UNSIGNED NOT NULL,
  `platform` ENUM('Facebook','LinkedIn','Instagram','X','Website') NOT NULL,
  `url`      VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_social_user_platform` (`user_id`,`platform`),
  CONSTRAINT `fk_social_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `volunteer_interests` (
  `user_id`  INT UNSIGNED NOT NULL,
  `category` ENUM('Community Development','Health and Wellness','Education and Awareness','Social and Cultural','Environment and Sustainability','Others') NOT NULL,
  PRIMARY KEY (`user_id`,`category`),
  CONSTRAINT `fk_interest_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  2. Issue reports
-- ---------------------------------------------------------------------

CREATE TABLE `reports` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `title`       VARCHAR(150) NOT NULL,
  `issue_type`  ENUM('Damaged Roads','Infrastructure Issues','Harassment Concerns','Hygiene Problems','Market Issues','Health Problems','Community Issues','Waste Disposal','Poor Parks','Overcrowding','Housing Shortage','Education Gaps','Corruption','Service Inefficiency','Flooding Challenges','Others') NOT NULL,
  `description` TEXT         NOT NULL,
  `division`    ENUM('Dhaka','Chattogram','Rajshahi','Khulna','Barishal','Sylhet','Rangpur','Mymensingh') NOT NULL,
  `area`        VARCHAR(100) NOT NULL,
  `address`     VARCHAR(255) DEFAULT NULL COMMENT 'Exact spot of the problem',
  `photo`       VARCHAR(255) DEFAULT NULL,
  `status`      ENUM('Pending','In Progress','Solved','Denied') NOT NULL DEFAULT 'Pending',
  `admin_note`  VARCHAR(500) DEFAULT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reports_user` (`user_id`),
  KEY `idx_reports_area_status` (`area`,`status`),
  KEY `idx_reports_created` (`created_at`),
  CONSTRAINT `fk_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `report_votes` (
  `report_id`  INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NOT NULL,
  `intensity`  ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`,`user_id`),
  KEY `idx_votes_user` (`user_id`),
  CONSTRAINT `fk_votes_report` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_votes_user`   FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  3. Community events
--     Lifecycle: active -> completed (by organiser)
--                       -> verified  (by a verified local representative)
--                       -> approved  (by an admin)
-- ---------------------------------------------------------------------

CREATE TABLE `events` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED NOT NULL,
  `title`        VARCHAR(150) NOT NULL,
  `category`     ENUM('Community Development','Health and Wellness','Education and Awareness','Social and Cultural','Environment and Sustainability','Others') NOT NULL,
  `description`  TEXT         NOT NULL,
  `division`     ENUM('Dhaka','Chattogram','Rajshahi','Khulna','Barishal','Sylhet','Rangpur','Mymensingh') NOT NULL,
  `area`         VARCHAR(100) NOT NULL,
  `venue`        VARCHAR(255) DEFAULT NULL,
  `event_date`   DATETIME     DEFAULT NULL,
  `contact`      VARCHAR(20)  NOT NULL,
  `status`       ENUM('active','completed','verified','approved') NOT NULL DEFAULT 'active',
  `completed_at` DATETIME     DEFAULT NULL,
  `verified_by`  INT UNSIGNED DEFAULT NULL,
  `verified_at`  DATETIME     DEFAULT NULL,
  `approved_by`  INT UNSIGNED DEFAULT NULL,
  `approved_at`  DATETIME     DEFAULT NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_events_user` (`user_id`),
  KEY `idx_events_area_status` (`area`,`status`),
  KEY `idx_events_created` (`created_at`),
  CONSTRAINT `fk_events_user`     FOREIGN KEY (`user_id`)     REFERENCES `users` (`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_events_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`)  ON DELETE SET NULL,
  CONSTRAINT `fk_events_approver` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `event_likes` (
  `event_id`   INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`,`user_id`),
  KEY `idx_likes_user` (`user_id`),
  CONSTRAINT `fk_likes_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_likes_user`  FOREIGN KEY (`user_id`)  REFERENCES `users` (`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `event_comments` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id`   INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NOT NULL,
  `body`       TEXT         NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comments_event` (`event_id`),
  KEY `idx_comments_user` (`user_id`),
  CONSTRAINT `fk_comments_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user`  FOREIGN KEY (`user_id`)  REFERENCES `users` (`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `event_participants` (
  `event_id`  INT UNSIGNED NOT NULL,
  `user_id`   INT UNSIGNED NOT NULL,
  `joined_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`,`user_id`),
  KEY `idx_participants_user` (`user_id`),
  CONSTRAINT `fk_participants_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_participants_user`  FOREIGN KEY (`user_id`)  REFERENCES `users` (`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fundraising: the organiser requests a target, a verified local
-- representative (other than the organiser) or an admin approves it,
-- then neighbours can record donations.
CREATE TABLE `event_funds` (
  `event_id`      INT UNSIGNED  NOT NULL,
  `target_amount` DECIMAL(12,2) NOT NULL,
  `purpose`       VARCHAR(255)  DEFAULT NULL,
  `status`        ENUM('pending','open','closed') NOT NULL DEFAULT 'pending',
  `approved_by`   INT UNSIGNED  DEFAULT NULL COMMENT 'Verified representative who approved',
  `approved_at`   DATETIME      DEFAULT NULL,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  CONSTRAINT `chk_fund_target` CHECK (`target_amount` > 0),
  CONSTRAINT `fk_funds_event`    FOREIGN KEY (`event_id`)    REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_funds_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `donations` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `event_id`       INT UNSIGNED  NOT NULL,
  `user_id`        INT UNSIGNED  NOT NULL,
  `amount`         DECIMAL(12,2) NOT NULL,
  `method`         ENUM('bKash','Nagad','Rocket','Bank','Cash') NOT NULL DEFAULT 'bKash',
  `transaction_ref` VARCHAR(100) DEFAULT NULL,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_donations_event` (`event_id`),
  KEY `idx_donations_user` (`user_id`),
  CONSTRAINT `chk_donation_amount` CHECK (`amount` > 0),
  CONSTRAINT `fk_donations_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_donations_user`  FOREIGN KEY (`user_id`)  REFERENCES `users` (`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  4. Blood donation network
-- ---------------------------------------------------------------------

CREATE TABLE `blood_donors` (
  `user_id`            INT UNSIGNED NOT NULL,
  `is_available`       TINYINT(1)   NOT NULL DEFAULT 1,
  `last_donation_date` DATE         DEFAULT NULL,
  `next_eligible_date` DATE         DEFAULT NULL COMMENT 'last_donation_date + 90 days',
  `created_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_donor_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A requester asks a donor for their phone number. The number is only
-- shown to the requester after the donor accepts.
CREATE TABLE `blood_requests` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `requester_id` INT UNSIGNED NOT NULL,
  `donor_id`     INT UNSIGNED NOT NULL,
  `message`      VARCHAR(500) DEFAULT NULL,
  `status`       ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `responded_at` DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_blood_req_requester` (`requester_id`),
  KEY `idx_blood_req_donor_status` (`donor_id`,`status`),
  CONSTRAINT `fk_blood_req_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_blood_req_donor`     FOREIGN KEY (`donor_id`)     REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  5. Safety: alerts, emergency contacts, SOS
-- ---------------------------------------------------------------------

CREATE TABLE `alerts` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`    INT UNSIGNED DEFAULT NULL,
  `title`       VARCHAR(150) NOT NULL,
  `description` TEXT         NOT NULL,
  `severity`    ENUM('info','warning','critical') NOT NULL DEFAULT 'warning',
  `division`    ENUM('Dhaka','Chattogram','Rajshahi','Khulna','Barishal','Sylhet','Rangpur','Mymensingh') DEFAULT NULL COMMENT 'NULL = whole country',
  `area`        VARCHAR(100) DEFAULT NULL COMMENT 'NULL = whole division',
  `start_date`  DATE         NOT NULL,
  `end_date`    DATE         NOT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_alerts_scope` (`division`,`area`),
  KEY `idx_alerts_dates` (`start_date`,`end_date`),
  CONSTRAINT `chk_alert_dates` CHECK (`end_date` >= `start_date`),
  CONSTRAINT `fk_alerts_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `emergency_contacts` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(150) NOT NULL,
  `category`   ENUM('Police','Fire Service','Hospital','Ambulance','Helpline','Utility','Other') NOT NULL DEFAULT 'Other',
  `phone`      VARCHAR(30)  NOT NULL,
  `address`    VARCHAR(255) DEFAULT NULL,
  `division`   ENUM('Dhaka','Chattogram','Rajshahi','Khulna','Barishal','Sylhet','Rangpur','Mymensingh') DEFAULT NULL COMMENT 'NULL = nationwide',
  `area`       VARCHAR(100) DEFAULT NULL COMMENT 'NULL = whole division',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contacts_scope` (`division`,`area`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sos_requests` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `message`     VARCHAR(500) DEFAULT NULL,
  `latitude`    DECIMAL(10,7) DEFAULT NULL,
  `longitude`   DECIMAL(10,7) DEFAULT NULL,
  `area`        VARCHAR(100) NOT NULL,
  `status`      ENUM('open','resolved') NOT NULL DEFAULT 'open',
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sos_status` (`status`),
  KEY `idx_sos_user` (`user_id`),
  CONSTRAINT `fk_sos_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  6. Messaging and notifications
-- ---------------------------------------------------------------------

CREATE TABLE `messages` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender_id`   INT UNSIGNED NOT NULL,
  `receiver_id` INT UNSIGNED NOT NULL,
  `body`        TEXT         DEFAULT NULL,
  `attachment`  VARCHAR(255) DEFAULT NULL,
  `is_read`     TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_messages_pair` (`sender_id`,`receiver_id`,`created_at`),
  KEY `idx_messages_receiver_read` (`receiver_id`,`is_read`),
  CONSTRAINT `fk_messages_sender`   FOREIGN KEY (`sender_id`)   REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `type`       ENUM('report','event','fund','blood','alert','sos','system') NOT NULL DEFAULT 'system',
  `message`    VARCHAR(500) NOT NULL,
  `link`       VARCHAR(255) DEFAULT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_read` (`user_id`,`is_read`,`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  7. Leaderboard view
--     Points: report = 5, event organised = 4, event approved = 6,
--             approved fundraiser = 10, event joined = 2
-- ---------------------------------------------------------------------

CREATE OR REPLACE VIEW `user_scores` AS
SELECT
  u.id, u.username, u.full_name, u.avatar, u.area, u.division, u.is_verified,
  COALESCE(r.cnt, 0)  AS reports_count,
  COALESCE(e.cnt, 0)  AS events_count,
  COALESCE(e.done, 0) AS events_approved,
  COALESCE(f.cnt, 0)  AS funds_count,
  COALESCE(p.cnt, 0)  AS participations,
  COALESCE(r.cnt, 0) * 5 + COALESCE(e.cnt, 0) * 4 + COALESCE(e.done, 0) * 6
    + COALESCE(f.cnt, 0) * 10 + COALESCE(p.cnt, 0) * 2 AS total_score
FROM users u
LEFT JOIN (SELECT user_id, COUNT(*) cnt FROM reports GROUP BY user_id) r ON r.user_id = u.id
LEFT JOIN (SELECT user_id, COUNT(*) cnt, SUM(status = 'approved') done FROM events GROUP BY user_id) e ON e.user_id = u.id
LEFT JOIN (SELECT ev.user_id, COUNT(*) cnt FROM event_funds ef JOIN events ev ON ev.id = ef.event_id
           WHERE ef.status IN ('open','closed') GROUP BY ev.user_id) f ON f.user_id = u.id
LEFT JOIN (SELECT user_id, COUNT(*) cnt FROM event_participants GROUP BY user_id) p ON p.user_id = u.id;

-- ---------------------------------------------------------------------
--  8. Starter data
-- ---------------------------------------------------------------------

-- Default administrator  (username: admin / password: admin123)
INSERT INTO `admins` (`username`, `full_name`, `password_hash`) VALUES
('admin', 'System Administrator', '$2y$10$LJ3VtSBJxS1kiitLpedM1e97Yg7WAEQrK4Rq//Ga0fg9MjV1jmeNu');

-- Nationwide emergency numbers (Bangladesh)
INSERT INTO `emergency_contacts` (`name`, `category`, `phone`, `address`, `division`, `area`) VALUES
('National Emergency Service',                      'Helpline',     '999',   'Police, fire service and ambulance',     NULL, NULL),
('Fire Service & Civil Defence',                    'Fire Service', '16163', 'Fire Service hotline',                   NULL, NULL),
('Women & Children Violence Prevention Helpline',   'Helpline',     '109',   'Ministry of Women and Children Affairs', NULL, NULL),
('National Information Service',                    'Helpline',     '333',   'Government information and services',    NULL, NULL),
('Anti-Corruption Commission Hotline',              'Helpline',     '106',   'Report corruption',                      NULL, NULL),
('Health Call Centre (Shasthya Batayon)',           'Hospital',     '16263', 'Medical advice by phone',                NULL, NULL);

SET FOREIGN_KEY_CHECKS = 1;
