@echo off
REM Aunt Joy's Restaurant - Database Setup Script (Windows)
REM This script sets up the database with schema and seeders

echo 🍽️  Aunt Joy's Restaurant - Database Setup
echo ==========================================

REM Database configuration
set DB_HOST=localhost
set DB_NAME=aunt_joys_restaurant
set DB_USER=root
set DB_PASS=

REM Paths
set SCHEMA_FILE=database\schema_with_inventory.sql
set SEEDERS_FILE=database\seeders_with_inventory.sql

echo 🔄 Checking if migration is needed...
mysql -h%DB_HOST% -u%DB_USER% -p%DB_PASS% %DB_NAME% -e "SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = '%DB_NAME%' AND table_name = 'users' AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = '%DB_NAME%' AND table_name = 'users' AND column_name = 'username')), 'Running migration...', 'Schema already up to date')" 2>nul

mysql -h%DB_HOST% -u%DB_USER% -p%DB_PASS% %DB_NAME% -e "SELECT IF(NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = '%DB_NAME%' AND table_name = 'users' AND column_name = 'username'), 'Running migration...', 'Username column already exists')" 2>nul

mysql -h%DB_HOST% -u%DB_USER% -p%DB_PASS% %DB_NAME% < database\migrate_to_inventory.sql 2>nul && echo ✅ Migration completed

echo 📋 Setting up database...

REM Create database if not exists
mysql -h%DB_HOST% -u%DB_USER% -p%DB_PASS% -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if %errorlevel% neq 0 (
    echo ❌ Failed to create database. Please check your MySQL credentials.
    pause
    exit /b 1
)

echo ✅ Database created/verified: %DB_NAME%

REM Import schema
echo 📝 Importing database schema...
mysql -h%DB_HOST% -u%DB_USER% -p%DB_PASS% %DB_NAME% < %SCHEMA_FILE%

if %errorlevel% neq 0 (
    echo ❌ Failed to import schema
    pause
    exit /b 1
)

echo ✅ Schema imported successfully

REM Import seeders
echo 🌱 Importing seeders (test data with inventory)...
mysql -h%DB_HOST% -u%DB_USER% -p%DB_PASS% %DB_NAME% < %SEEDERS_FILE%

if %errorlevel% neq 0 (
    echo ❌ Failed to import seeders
    pause
    exit /b 1
)

echo ✅ Seeders imported successfully
echo 🔄 Updating meal availability based on inventory...
mysql -h%DB_HOST% -u%DB_USER% -p%DB_PASS% %DB_NAME% -e "CALL update_meal_availability_from_inventory();"
echo ✅ Meal availability updated based on current stock levels

echo.
echo 🎉 Database setup completed successfully!
echo.
echo 📊 Test Data Summary:
echo - 10+ test users ^(Customers, Admins, Sales, Managers^)
echo - 9 sample orders with different statuses
echo - Order items and status history
echo - Additional test meals
echo.
echo 🔑 Test Credentials ^(Password: password123^):
echo.
echo CUSTOMERS:
echo - jchikwanda / joyce.chikwanda@email.com ^(Joyce Chikwanda^)
echo - mphiri / mphiri@email.com ^(Michael Phiri^)
echo - mbanda / angela.mbanda@email.com ^(Angela Banda^)
echo - kaziwe / samuel.kaziwe@email.com ^(Samuel Kaziwe^)
echo.
echo ADMINISTRATORS:
echo - admin / admin@auntjoy.test ^(System Administrator^)
echo - jadmin / joyce.admin@auntjoy.test ^(Joyce Admin^)
echo.
echo SALES PERSONNEL:
echo - jsales / john.sales@auntjoy.test ^(John Sales^)
echo - msales / mary.sales@auntjoy.test ^(Mary Sales^)
echo.
echo MANAGERS:
echo - jmanager / james.manager@auntjoy.test ^(James Manager^)
echo - smanager / sarah.manager@auntjoy.test ^(Sarah Manager^)
echo.
echo 🌐 Access the application at: http://localhost/aunt_joy
echo.
pause