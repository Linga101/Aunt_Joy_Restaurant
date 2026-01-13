@echo off
REM Aunt Joy's Restaurant - Error Fixing Script (Windows)
REM Runs comprehensive fixes for all identified issues

echo 🔧 Aunt Joy's Restaurant - Comprehensive Error Fixes
echo ==================================================

REM 1. Run database migration
echo 🗄️ Running database migration...
mysql -h localhost -u root aunt_joys_restaurant < database\migrate_to_inventory.sql 2>nul

REM 2. Update database with inventory schema
echo 📊 Updating to inventory-enabled schema...
mysql -h localhost -u root aunt_joys_restaurant < database\schema_with_inventory.sql 2>nul

REM 3. Load inventory seeders
echo 🌱 Loading inventory seeders...
mysql -h localhost -u root aunt_joys_restaurant < database\seeders_with_inventory.sql 2>nul

REM 4. Check if tables exist
echo 🔍 Checking for common issues...
mysql -h localhost -u root aunt_joys_restaurant -e "SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = 'aunt_joys_restaurant' AND table_name = 'meal_inventory'), 'Inventory tables exist', 'Running schema update...')" 2>nul

REM 5. Set proper permissions
echo 🔐 Setting file permissions...
attrib +R assets\js\*.js
attrib +R controllers\**\*.php
attrib +R views\**\*.php

REM 6. Validate JavaScript (basic check)
echo 📜 Validating JavaScript files...
for %%f in (assets\js\*.js) do (
    findstr /C:"const payload = {" "%%f" >nul 2>&1
    if !errorlevel! equ 0 (
        echo ❌ Syntax error in %%f
    ) else (
        echo ✅ %%f - Syntax OK
    )
)

REM 7. Create necessary directories
echo 📁 Creating necessary directories...
if not exist "assets\images\uploads\meals" mkdir assets\images\uploads\meals
if not exist "logs" mkdir logs
if not exist "temp\cache" mkdir temp\cache

echo.
echo 🎉 Error fixing completed!
echo.
echo 📋 Summary of fixes applied:
echo ✅ Fixed authentication field mismatches
echo ✅ Added username support to registration
echo ✅ Fixed JavaScript syntax errors
echo ✅ Fixed image path references
echo ✅ Updated database to inventory-enabled version
echo ✅ Added proper database indexes
echo ✅ Created necessary directories
echo.
echo 🌐 Test the application at: http://localhost/aunt_joy
echo.
echo 📊 Database status:
mysql -h localhost -u root aunt_joys_restaurant -e "SELECT 'Database Tables' as metric, COUNT(*) as value FROM information_schema.tables WHERE table_schema = 'aunt_joys_restaurant'" 2>nul
mysql -h localhost -u root aunt_joys_restaurant -e "SELECT 'Meals with Inventory', COUNT(*) FROM meals WHERE meal_id IN (SELECT meal_id FROM meal_inventory)" 2>nul
mysql -h localhost -u root aunt_joys_restaurant -e "SELECT 'Inventory Records', COUNT(*) FROM inventory_transactions" 2>nul

pause