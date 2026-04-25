# Database Install Scripts

These scripts create a Calixy database and application user for each supported server engine:

- `mysql-install.sql`
- `mariadb-install.sql`
- `mariadb-reset-user.sql`
- `postgresql-install.sql`
- `mssql-install.sql`

Before running a script, update the default database name, user name, and password at the top of the file.

Use:

- `mysql-install.sql` with MySQL
- `mariadb-install.sql` with MariaDB for a fresh install or a clean rerun
- `mariadb-reset-user.sql` with MariaDB to create or repair the database, user password, and grants
- `postgresql-install.sql` with `psql`
- `mssql-install.sql` with SQL Server Management Studio or `sqlcmd`

After the database and user exist, point Calixy at that connection in `.env` and let Calixy create its tables on boot or with `php artisan unified-appointments:install`.
