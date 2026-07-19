-- Create both instance databases and grant the app user access.
-- NOTE: MySQL init scripts do NOT expand shell/env vars, so the user below is a
-- literal. It must match DB_USER in .env (default: twint). If you change DB_USER,
-- update this GRANT too (or drop the volume so this re-runs).
CREATE DATABASE IF NOT EXISTS wc_latest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS wc_oldest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON wc_latest.* TO 'twint'@'%';
GRANT ALL PRIVILEGES ON wc_oldest.* TO 'twint'@'%';
FLUSH PRIVILEGES;
