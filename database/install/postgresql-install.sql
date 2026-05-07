-- Calixy PostgreSQL bootstrap script (generic SQL editors).
-- Use this file with pgAdmin, DBeaver, DataGrip, or similar tools.
-- Run as a superuser or another role that can create roles and databases.
-- It does not create application login accounts. Usernames are stored by
-- Laravel migrations in the users.name column; run php artisan migrate --seed
-- after this database exists.
--
-- Search/replace these defaults:
--   calixy
--   calixy_user
--   ChangeMeNow!123
--
-- IMPORTANT:
-- 1) Run Section 1 while connected to a maintenance database (usually "postgres").
-- 2) Run the CREATE DATABASE statement as a single statement with auto-commit ON.
--    In many SQL tools, running a full script in one transaction will prevent
--    CREATE DATABASE from executing.
-- 3) Reconnect to database "calixy", then run Section 2.
--
-- Troubleshooting:
-- - SQLSTATE 3D000 "database \"calixy\" does not exist":
--   your editor is connected to "calixy" (or trying to use it) before creation.
--   Connect to "postgres", run only Section 1, then reconnect to "calixy".

-- Section 1: role + database creation (run in database "postgres")
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_catalog.pg_roles
        WHERE rolname = 'calixy_user'
    ) THEN
        EXECUTE format(
            'CREATE ROLE %I LOGIN PASSWORD %L',
            'calixy_user',
            'ChangeMeNow!123'
        );
    ELSE
        EXECUTE format(
            'ALTER ROLE %I WITH LOGIN PASSWORD %L',
            'calixy_user',
            'ChangeMeNow!123'
        );
    END IF;
END
$$;

-- Run this line alone (single statement) with auto-commit ON.
-- If the database already exists, skip this line.
CREATE DATABASE "calixy" OWNER "calixy_user";

GRANT ALL PRIVILEGES ON DATABASE "calixy" TO "calixy_user";

-- Section 2: reconnect to database "calixy", then run below.
GRANT USAGE, CREATE ON SCHEMA public TO "calixy_user";
ALTER SCHEMA public OWNER TO "calixy_user";
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO "calixy_user";
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO "calixy_user";
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON FUNCTIONS TO "calixy_user";
