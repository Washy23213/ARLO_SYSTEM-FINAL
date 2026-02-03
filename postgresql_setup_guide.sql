-- ===========================================
-- LARAVEL POSTGRESQL CONFIGURATION GUIDE
-- ===========================================

-- 1. Install PostgreSQL (if not already installed)
-- Ubuntu/Debian:
-- sudo apt update
-- sudo apt install postgresql postgresql-contrib

-- macOS (with Homebrew):
-- brew install postgresql
-- brew services start postgresql

-- Windows: Download from postgresql.org

-- 2. Create database and user
-- Connect to PostgreSQL as superuser (usually 'postgres')
-- psql -U postgres

-- Run these commands in psql:
CREATE DATABASE cmu_arlo_db;
CREATE USER cmu_user WITH ENCRYPTED PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE cmu_arlo_db TO cmu_user;
ALTER DATABASE cmu_arlo_db OWNER TO cmu_user;

-- 3. Enable UUID extension in the database
-- Connect to your database:
-- psql -U cmu_user -d cmu_arlo_db
-- Then run:
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- 4. Update Laravel .env file
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cmu_arlo_db
DB_USERNAME=cmu_user
DB_PASSWORD=your_secure_password

-- 5. Install PHP PostgreSQL extension
-- Ubuntu/Debian:
-- sudo apt install php8.2-pgsql  # (adjust PHP version)

-- macOS:
-- May be included with PHP installation

-- Windows: Uncomment extension=pgsql in php.ini

-- 6. Update Laravel User model for UUID
-- In app/Models/User.php, add:
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    use HasUuids; // Add this line

    // ... rest of the model
}

-- 7. Update config/database.php for PostgreSQL
-- Make sure the pgsql connection is properly configured

-- 8. Run migrations (if using Laravel migrations)
-- php artisan migrate

-- Or run the provided schema files directly in psql

-- ===========================================
-- USEFUL POSTGRESQL COMMANDS
-- ===========================================

-- Connect to database
psql -U cmu_user -d cmu_arlo_db

-- List all databases
\l

-- List all tables in current database
\dt

-- Describe a table
\d table_name

-- Show table structure with indexes
\d+ table_name

-- View running queries (as superuser)
SELECT pid, age(clock_timestamp(), query_start), usename, query
FROM pg_stat_activity
WHERE query != '<IDLE>' AND query NOT ILIKE '%pg_stat_activity%'
ORDER BY query_start desc;

-- Check database size
SELECT pg_size_pretty(pg_database_size('cmu_arlo_db'));

-- Check table sizes
SELECT schemaname, tablename, pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;

-- Backup database
pg_dump -U cmu_user -h localhost cmu_arlo_db > cmu_arlo_backup.sql

-- Restore database
psql -U cmu_user -d cmu_arlo_db < cmu_arlo_backup.sql

-- ===========================================
-- PERFORMANCE OPTIMIZATION
-- ===========================================

-- Analyze table statistics (run after bulk inserts)
ANALYZE users;
ANALYZE jobs;
ANALYZE announcements;
-- etc.

-- Create additional indexes if needed
CREATE INDEX CONCURRENTLY idx_jobs_created_at ON jobs(created_at);
CREATE INDEX CONCURRENTLY idx_announcements_published_at ON announcements(published_at);

-- Monitor slow queries (add to postgresql.conf)
# log_min_duration_statement = 1000  # Log queries taking more than 1 second

-- ===========================================
-- SECURITY BEST PRACTICES
-- ===========================================

-- Create read-only user for analytics
CREATE USER analytics_user WITH PASSWORD 'analytics_password';
GRANT CONNECT ON DATABASE cmu_arlo_db TO analytics_user;
GRANT USAGE ON SCHEMA public TO analytics_user;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO analytics_user;

-- Create application user with limited permissions
-- (Use the cmu_user we created earlier, but limit its permissions)
REVOKE ALL ON ALL TABLES IN SCHEMA public FROM cmu_user;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO cmu_user;
GRANT USAGE ON ALL SEQUENCES IN SCHEMA public TO cmu_user;

-- Enable row-level security if needed for multi-tenant setup
-- ALTER TABLE users ENABLE ROW LEVEL SECURITY;

-- ===========================================
-- MONITORING QUERIES
-- ===========================================

-- Active connections
SELECT count(*) as active_connections FROM pg_stat_activity;

-- Table bloat check
SELECT
    schemaname, tablename,
    n_tup_ins - n_tup_del as row_count,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size
FROM pg_stat_user_tables
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;

-- Index usage
SELECT
    schemaname, tablename, indexname,
    idx_scan, idx_tup_read, idx_tup_fetch
FROM pg_stat_user_indexes
ORDER BY idx_scan DESC;