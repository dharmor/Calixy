-- Calixy MariaDB install script
-- Update the database name, user name, and password below before running this
-- file as a privileged MariaDB user.
--
-- This script is safe for a first install and can be rerun to recreate the
-- application user with the password and grants shown below.
--
-- Search/replace these defaults:
--   calixy
--   calixy_user
--   ChangeMeNow!123

CREATE DATABASE IF NOT EXISTS `calixy`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

DROP USER IF EXISTS 'calixy_user'@'localhost';
DROP USER IF EXISTS 'calixy_user'@'%';

CREATE USER 'calixy_user'@'localhost' IDENTIFIED BY 'ChangeMeNow!123';
CREATE USER 'calixy_user'@'%' IDENTIFIED BY 'ChangeMeNow!123';

GRANT ALL PRIVILEGES ON `calixy`.* TO 'calixy_user'@'localhost';
GRANT ALL PRIVILEGES ON `calixy`.* TO 'calixy_user'@'%';

FLUSH PRIVILEGES;

SELECT 'Calixy MariaDB database and user are ready.' AS status_message;
