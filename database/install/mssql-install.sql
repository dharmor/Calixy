-- Calixy Microsoft SQL Server bootstrap script
-- Update the values below before running this file as a sysadmin or another login with equivalent rights.

DECLARE @DatabaseName sysname = N'calixy';
DECLARE @LoginName sysname = N'calixy_user';
DECLARE @LoginPassword nvarchar(128) = N'ChangeMeNow!123';

IF DB_ID(@DatabaseName) IS NULL
BEGIN
    DECLARE @CreateDatabaseSql nvarchar(max) = N'CREATE DATABASE ' + QUOTENAME(@DatabaseName) + N';';
    EXEC (@CreateDatabaseSql);
END;
GO

DECLARE @LoginName sysname = N'calixy_user';
DECLARE @LoginPassword nvarchar(128) = N'ChangeMeNow!123';

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
             CHECK_EXPIRATION = OFF;
    ';
    EXEC (@CreateLoginSql);
END;
GO

DECLARE @DatabaseName sysname = N'calixy';
DECLARE @LoginName sysname = N'calixy_user';

DECLARE @UseDatabaseSql nvarchar(max) = N'
USE ' + QUOTENAME(@DatabaseName) + N';

IF NOT EXISTS (
    SELECT 1
    FROM sys.database_principals
    WHERE name = N''' + REPLACE(@LoginName, '''', '''''') + N'''
)
BEGIN
    CREATE USER ' + QUOTENAME(@LoginName) + N' FOR LOGIN ' + QUOTENAME(@LoginName) + N';
END;

IF IS_ROLEMEMBER(N''db_owner'', N''' + REPLACE(@LoginName, '''', '''''') + N''') <> 1
BEGIN
    ALTER ROLE db_owner ADD MEMBER ' + QUOTENAME(@LoginName) + N';
END;
';

EXEC (@UseDatabaseSql);
GO
