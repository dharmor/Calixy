-- Calixy Microsoft SQL Server bootstrap script
-- Update the values below before running this file as a sysadmin or another login with equivalent rights.
-- This script is written as a single batch so it works in runners that do not support GO separators.
-- It does not create application login accounts. Usernames are stored by
-- Laravel migrations in the users.name column; run php artisan migrate --seed
-- after this database exists.

SET NOCOUNT ON;

DECLARE @DatabaseName sysname = N'calixy';
DECLARE @LoginName sysname = N'calixy_user';
DECLARE @LoginPassword nvarchar(128) = N'ChangeMeNow!123';

IF DB_ID(@DatabaseName) IS NULL
BEGIN
    DECLARE @CreateDatabaseSql nvarchar(max) = N'CREATE DATABASE ' + QUOTENAME(@DatabaseName) + N';';
    EXEC sys.sp_executesql @CreateDatabaseSql;
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.server_principals
    WHERE name = @LoginName
)
BEGIN
    DECLARE @CreateLoginSql nvarchar(max) = N'
CREATE LOGIN ' + QUOTENAME(@LoginName) + N'
WITH PASSWORD = ' + QUOTENAME(@LoginPassword, '''') + N',
     CHECK_POLICY = ON,
     CHECK_EXPIRATION = OFF;';

    EXEC sys.sp_executesql @CreateLoginSql;
END;

DECLARE @EnsureUserSql nvarchar(max) = N'
USE ' + QUOTENAME(@DatabaseName) + N';

IF NOT EXISTS (
    SELECT 1
    FROM sys.database_principals
    WHERE name = @UserName
)
BEGIN
    CREATE USER ' + QUOTENAME(@LoginName) + N' FOR LOGIN ' + QUOTENAME(@LoginName) + N';
END;

IF IS_ROLEMEMBER(N''db_owner'', @UserName) <> 1
BEGIN
    ALTER ROLE db_owner ADD MEMBER ' + QUOTENAME(@LoginName) + N';
END;';

EXEC sys.sp_executesql
    @EnsureUserSql,
    N'@UserName sysname',
    @UserName = @LoginName;

SELECT N'Calixy database and user are ready.' AS status_message;
