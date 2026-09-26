-- ==========================================================
--  Prepexus - hardening migration
--
--  Run this against the EXISTING "prepexus" database once.
--  It is separate from prepexus.sql, which is the fresh-install
--  dump and already contains everything below.
--
--  Usage:
--    C:\xampp\mysql\bin\mysql.exe -u root prepexus < database\migrate_hardening.sql
--
--  Safe to run more than once.
-- ==========================================================

-- ------------------------------------------------------------
--  Step 1: report duplicate emails.
--
--  The UNIQUE index in Step 3 cannot be created while duplicates
--  exist. If this returns any rows, resolve them first (decide
--  which account to keep, then delete or re-address the other),
--  otherwise Step 3 will fail.
-- ------------------------------------------------------------
SELECT email, COUNT(*) AS copies, GROUP_CONCAT(id) AS ids
FROM users
GROUP BY email
HAVING COUNT(*) > 1;

-- ------------------------------------------------------------
--  Step 2: login_attempts, the backing store for the login
--  throttle in includes/security.php.
--
--  The application also creates this on demand, so a failure here
--  is not fatal - the code handles a missing table.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` datetime NOT NULL,
  `was_successful` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `email_ip_time` (`email`, `ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
--  Step 3: UNIQUE index on users.email.
--
--  Closes the registration race: two concurrent signups can both
--  pass the "SELECT id FROM users WHERE email = ?" pre-check, so
--  only a database constraint can actually prevent duplicates.
--
--  NOTE: emails are stored lower-cased by the application, but
--  normalise any pre-existing mixed-case rows first, or this
--  statement will fail on a duplicate.
-- ------------------------------------------------------------
UPDATE `users` SET `email` = LOWER(TRIM(`email`));

ALTER TABLE `users`
  ADD UNIQUE KEY `email` (`email`);

-- ------------------------------------------------------------
--  Step 4: confirm
-- ------------------------------------------------------------
SHOW INDEX FROM `users`;
