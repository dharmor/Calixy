-- Calixy PostgreSQL bootstrap script
-- Run with psql as a superuser or another role that can create roles and databases.
-- Update the values below before running this file.

\set database_name calixy
\set database_user calixy_user
\set database_password ChangeMeNow!123

SELECT format(
    'CREATE ROLE %I LOGIN PASSWORD %L',
    :'database_user',
    :'database_password'
)
WHERE NOT EXISTS (
    SELECT 1
    FROM pg_catalog.pg_roles
    WHERE rolname = :'database_user'
)
\gexec

SELECT format(
    'CREATE DATABASE %I OWNER %I',
    :'database_name',
    :'database_user'
)
WHERE NOT EXISTS (
    SELECT 1
    FROM pg_database
    WHERE datname = :'database_name'
)
\gexec

\connect :database_name

GRANT ALL PRIVILEGES ON DATABASE :"database_name" TO :"database_user";
GRANT USAGE, CREATE ON SCHEMA public TO :"database_user";
ALTER SCHEMA public OWNER TO :"database_user";
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO :"database_user";
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO :"database_user";
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON FUNCTIONS TO :"database_user";

