#!/bin/bash

# Aunt Joy's Restaurant - Database Setup Script
# This script sets up the database with schema and seeders

echo "🍽️  Aunt Joy's Restaurant - Database Setup"
echo "=========================================="

# Database configuration
DB_HOST="localhost"
DB_NAME="aunt_joys_restaurant"
DB_USER="root"
DB_PASS=""

# Paths
SCHEMA_FILE="database/schema_with_inventory.sql"
SEEDERS_FILE="database/seeders_with_inventory.sql"

# Run migration first if basic schema exists
echo "🔄 Checking if migration is needed..."
mysql -h$DB_HOST -u$DB_USER -p$DB_PASS $DB_NAME -e "
SELECT IF(
    EXISTS(
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = '$DB_NAME' 
        AND table_name = 'users'
        AND NOT EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_schema = '$DB_NAME' 
            AND table_name = 'users' 
            AND column_name = 'username'
        )
    ),
    'Running migration...',
    'Schema already up to date'
) as migration_status;"

# Run migration if needed (check if username column exists)
mysql -h$DB_HOST -u$DB_USER -p$DB_PASS $DB_NAME -e "
SELECT IF(
    NOT EXISTS(
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = '$DB_NAME' 
        AND table_name = 'users' 
        AND column_name = 'username'
    ),
    'Running migration...',
    'Username column already exists'
) as username_check;"

# Run migration if username column doesn't exist
mysql -h$DB_HOST -u$DB_USER -p$DB_PASS $DB_NAME < database/migrate_to_inventory.sql 2>/dev/null || echo "✅ Migration completed"

echo "📋 Setting up database..."

# Create database if not exists
mysql -h$DB_HOST -u$DB_USER -p$DB_PASS -e "CREATE DATABASE IF NOT EXISTS $DB_NAME DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if [ $? -eq 0 ]; then
    echo "✅ Database created/verified: $DB_NAME"
else
    echo "❌ Failed to create database. Please check your MySQL credentials."
    exit 1
fi

# Import schema
echo "📝 Importing database schema..."
mysql -h$DB_HOST -u$DB_USER -p$DB_PASS $DB_NAME < $SCHEMA_FILE

if [ $? -eq 0 ]; then
    echo "✅ Schema imported successfully"
else
    echo "❌ Failed to import schema"
    exit 1
fi

# Import seeders
echo "🌱 Importing seeders (test data with inventory)..."
mysql -h$DB_HOST -u$DB_USER -p$DB_PASS $DB_NAME < $SEEDERS_FILE

if [ $? -eq 0 ]; then
    echo "✅ Seeders imported successfully"
    echo "🔄 Updating meal availability based on inventory..."
    mysql -h$DB_HOST -u$DB_USER -p$DB_PASS $DB_NAME -e "CALL update_meal_availability_from_inventory();"
    echo "✅ Meal availability updated based on current stock levels"
else
    echo "❌ Failed to import seeders"
    exit 1
fi

echo ""
echo "🎉 Database setup completed successfully!"
echo ""
echo "📊 Test Data Summary:"
echo "- 10+ test users (Customers, Admins, Sales, Managers)"
echo "- 9 sample orders with different statuses"
echo "- Order items and status history"
echo "- Additional test meals"
echo ""
echo "🔑 Test Credentials (Password: password123):"
echo ""
echo "CUSTOMERS:"
echo "- jchikwanda@joyce.chikwanda@email.com (Joyce Chikwanda)"
echo "- mphiri@mphiri@email.com (Michael Phiri)"
echo "- mbanda@angela.mbanda@email.com (Angela Banda)"
echo "- kaziwe@samuel.kaziwe@email.com (Samuel Kaziwe)"
echo ""
echo "ADMINISTRATORS:"
echo "- admin@admin@auntjoy.test (System Administrator)"
echo "- jadmin@joyce.admin@auntjoy.test (Joyce Admin)"
echo ""
echo "SALES PERSONNEL:"
echo "- jsales@john.sales@auntjoy.test (John Sales)"
echo "- msales@mary.sales@auntjoy.test (Mary Sales)"
echo ""
echo "MANAGERS:"
echo "- jmanager@james.manager@auntjoy.test (James Manager)"
echo "- smanager@sarah.manager@auntjoy.test (Sarah Manager)"
echo ""
echo "🌐 Access the application at: http://localhost/aunt_joy"
echo ""