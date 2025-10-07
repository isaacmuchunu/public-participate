#!/bin/bash

# PostgreSQL Migration Script for Public Participate Platform
# This script migrates data from SQLite to PostgreSQL with validation

set -e  # Exit on error

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
SQLITE_DB="$PROJECT_ROOT/database/database.sqlite"
BACKUP_DIR="$PROJECT_ROOT/storage/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== Public Participate PostgreSQL Migration ===${NC}"
echo ""

# Check prerequisites
echo "Checking prerequisites..."

if [ ! -f "$SQLITE_DB" ]; then
    echo -e "${RED}ERROR: SQLite database not found at $SQLITE_DB${NC}"
    exit 1
fi

if ! command -v psql &> /dev/null; then
    echo -e "${RED}ERROR: PostgreSQL client (psql) not found${NC}"
    echo "Install with: sudo apt-get install postgresql-client"
    exit 1
fi

if ! command -v php &> /dev/null; then
    echo -e "${RED}ERROR: PHP not found${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Prerequisites met${NC}"
echo ""

# Load environment variables
if [ -f "$PROJECT_ROOT/.env" ]; then
    source <(grep -v '^#' "$PROJECT_ROOT/.env" | sed 's/\r$//' | awk '/=/ {print $0}')
else
    echo -e "${RED}ERROR: .env file not found${NC}"
    exit 1
fi

# Get PostgreSQL connection details
read -p "PostgreSQL Host [$DB_HOST]: " PG_HOST
PG_HOST=${PG_HOST:-$DB_HOST}

read -p "PostgreSQL Port [5432]: " PG_PORT
PG_PORT=${PG_PORT:-5432}

read -p "PostgreSQL Database [public_participate]: " PG_DATABASE
PG_DATABASE=${PG_DATABASE:-public_participate}

read -p "PostgreSQL Username [postgres]: " PG_USERNAME
PG_USERNAME=${PG_USERNAME:-postgres}

read -s -p "PostgreSQL Password: " PG_PASSWORD
echo ""

export PGPASSWORD="$PG_PASSWORD"

# Test PostgreSQL connection
echo ""
echo "Testing PostgreSQL connection..."
if ! psql -h "$PG_HOST" -p "$PG_PORT" -U "$PG_USERNAME" -d postgres -c '\q' 2>/dev/null; then
    echo -e "${RED}ERROR: Cannot connect to PostgreSQL${NC}"
    exit 1
fi
echo -e "${GREEN}✓ PostgreSQL connection successful${NC}"
echo ""

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Backup SQLite database
echo "Creating SQLite backup..."
cp "$SQLITE_DB" "$BACKUP_DIR/database_backup_$TIMESTAMP.sqlite"
echo -e "${GREEN}✓ Backup created at $BACKUP_DIR/database_backup_$TIMESTAMP.sqlite${NC}"
echo ""

# Create PostgreSQL database if it doesn't exist
echo "Creating PostgreSQL database..."
psql -h "$PG_HOST" -p "$PG_PORT" -U "$PG_USERNAME" -d postgres -c "CREATE DATABASE $PG_DATABASE;" 2>/dev/null || true
echo -e "${GREEN}✓ Database $PG_DATABASE ready${NC}"
echo ""

# Update .env file
echo "Updating .env configuration..."
sed -i.backup "s/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/" "$PROJECT_ROOT/.env"
sed -i "s/^DB_HOST=.*/DB_HOST=$PG_HOST/" "$PROJECT_ROOT/.env"
sed -i "s/^DB_PORT=.*/DB_PORT=$PG_PORT/" "$PROJECT_ROOT/.env"
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$PG_DATABASE/" "$PROJECT_ROOT/.env"
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=$PG_USERNAME/" "$PROJECT_ROOT/.env"
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=$PG_PASSWORD/" "$PROJECT_ROOT/.env"
echo -e "${GREEN}✓ .env updated (backup saved as .env.backup)${NC}"
echo ""

# Run migrations on PostgreSQL
echo "Running migrations on PostgreSQL..."
cd "$PROJECT_ROOT"
php artisan migrate:fresh --force
echo -e "${GREEN}✓ Migrations completed${NC}"
echo ""

# Export SQLite data and import to PostgreSQL
echo "Migrating data from SQLite to PostgreSQL..."

# Get list of tables
TABLES=$(sqlite3 "$SQLITE_DB" ".tables" | tr ' ' '\n' | grep -v '^$')

for TABLE in $TABLES; do
    # Skip migrations and cache tables
    if [[ "$TABLE" == "migrations" ]] || [[ "$TABLE" == "cache"* ]] || [[ "$TABLE" == "jobs"* ]] || [[ "$TABLE" == "sessions" ]]; then
        echo "Skipping $TABLE..."
        continue
    fi

    echo "Migrating table: $TABLE"

    # Export to CSV
    sqlite3 "$SQLITE_DB" <<EOF
.headers on
.mode csv
.output /tmp/${TABLE}_$TIMESTAMP.csv
SELECT * FROM $TABLE;
.quit
EOF

    # Check if CSV has data
    if [ -s "/tmp/${TABLE}_$TIMESTAMP.csv" ]; then
        # Import to PostgreSQL
        ROW_COUNT=$(wc -l < "/tmp/${TABLE}_$TIMESTAMP.csv")
        ROW_COUNT=$((ROW_COUNT - 1))  # Subtract header

        if [ $ROW_COUNT -gt 0 ]; then
            psql -h "$PG_HOST" -p "$PG_PORT" -U "$PG_USERNAME" -d "$PG_DATABASE" <<EOF
\COPY $TABLE FROM '/tmp/${TABLE}_$TIMESTAMP.csv' WITH (FORMAT csv, HEADER true, NULL '');
EOF
            echo -e "${GREEN}  ✓ Migrated $ROW_COUNT rows${NC}"
        else
            echo "  (empty table)"
        fi

        # Clean up CSV
        rm "/tmp/${TABLE}_$TIMESTAMP.csv"
    else
        echo "  (no data)"
    fi
done

echo ""

# Validate data integrity
echo "Validating data integrity..."
php artisan tinker --execute="
    \$stats = [
        'users' => \App\Models\User::count(),
        'bills' => \App\Models\Bill::count(),
        'submissions' => \App\Models\Submission::count(),
        'counties' => \App\Models\County::count(),
        'constituencies' => \App\Models\Constituency::count(),
        'wards' => \App\Models\Ward::count(),
    ];
    foreach (\$stats as \$model => \$count) {
        echo \"\$model: \$count\\n\";
    }
"
echo ""

# Reset sequences for PostgreSQL
echo "Resetting PostgreSQL sequences..."
psql -h "$PG_HOST" -p "$PG_PORT" -U "$PG_USERNAME" -d "$PG_DATABASE" <<'EOF'
DO $$
DECLARE
    r RECORD;
BEGIN
    FOR r IN
        SELECT schemaname, tablename
        FROM pg_tables
        WHERE schemaname = 'public' AND tablename NOT LIKE '%_pkey'
    LOOP
        BEGIN
            EXECUTE format('SELECT setval(pg_get_serial_sequence(''%I.%I'', ''id''), COALESCE((SELECT MAX(id) FROM %I.%I), 1), true);',
                r.schemaname, r.tablename, r.schemaname, r.tablename);
        EXCEPTION
            WHEN OTHERS THEN
                -- Skip tables without id column
                CONTINUE;
        END;
    END LOOP;
END $$;
EOF
echo -e "${GREEN}✓ Sequences reset${NC}"
echo ""

# Run seeder to ensure reference data is complete
echo "Running seeders..."
php artisan db:seed --force
echo -e "${GREEN}✓ Seeders completed${NC}"
echo ""

# Clear cache
echo "Clearing application cache..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear
echo -e "${GREEN}✓ Cache cleared${NC}"
echo ""

echo -e "${GREEN}=== Migration Completed Successfully ===${NC}"
echo ""
echo "Next steps:"
echo "1. Verify application functionality"
echo "2. Test all critical workflows"
echo "3. Monitor PostgreSQL performance"
echo "4. Remove SQLite backup after verification"
echo ""
echo "Backup location: $BACKUP_DIR/database_backup_$TIMESTAMP.sqlite"
echo "Environment backup: $PROJECT_ROOT/.env.backup"
echo ""
