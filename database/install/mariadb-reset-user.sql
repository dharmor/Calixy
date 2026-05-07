-- Calixy MariaDB bootstrap / user reset script
-- Use this to create or repair the Calixy MariaDB database, user password,
-- and grants so they match the values in `.env`.
-- It does not create application login accounts. Usernames are stored by
-- Laravel migrations in the users.name column; run php artisan migrate --seed
-- after this database exists.
--
-- Update the defaults below before running this file as a privileged MariaDB user.
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

SELECT 'Calixy MariaDB database and user are ready.' AS status_message;
