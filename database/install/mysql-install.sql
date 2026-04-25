-- Calixy MySQL / MariaDB bootstrap script
-- Update the database name, user name, and password below before running this file
-- as a privileged MySQL user.
--
-- This script is safe to run on a first install and can be rerun to reset the
-- application user's password and grants to the values below.
--
-- Search/replace these defaults:
--   calixy
--   calixy_user
--   ChangeMeNow!123

CREATE DATABASE IF NOT EXISTS `calixy`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'calixy_user'@'localhost' IDENTIFIED BY 'ChangeMeNow!123';
ALTER USER 'calixy_user'@'localhost' IDENTIFIED BY 'ChangeMeNow!123';

CREATE USER IF NOT EXISTS 'calixy_user'@'%' IDENTIFIED BY 'ChangeMeNow!123';
ALTER USER 'calixy_user'@'%' IDENTIFIED BY 'ChangeMeNow!123';

GRANT ALL PRIVILEGES ON `calixy`.* TO 'calixy_user'@'localhost';
GRANT ALL PRIVILEGES ON `calixy`.* TO 'calixy_user'@'%';

FLUSH PRIVILEGES;

SELECT 'Calixy database and user are ready.' AS status_message;
