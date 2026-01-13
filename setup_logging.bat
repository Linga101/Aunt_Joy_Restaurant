@echo off
REM Aunt Joy's Restaurant - Logging Setup Script (Windows)
REM Sets up proper logging configuration and directories

echo 📝 Aunt Joy's Restaurant - Logging Setup
echo ==========================================

REM Create logs directory
echo 📁 Creating logs directory...
if not exist logs mkdir logs

REM Set proper permissions
echo 🔐 Setting permissions...
attrib +R logs
attrib +666 logs\*.log

REM Create log files
echo 📄 Creating log files...
echo. > logs\application.log
echo. > logs\security.log
echo. > logs\business.log
echo. > logs\php_errors.log
echo. > logs\access.log

REM Create .htaccess for logging
echo ⚙️ Creating logging configuration...
(
echo # Restrict access to log files
echo ^logs\*.log^ > logs\.htaccess
echo # Deny access from web
echo Deny from all^
) >> logs\.htaccess

REM PHP Configuration
echo 🐘 Setting up PHP logging...
(
echo # PHP Error Logging
echo php_value log_errors On
echo php_value error_log "C:\xampp\htdocs\aunt_joy\logs\php_errors.log"
echo php_value error_reporting E_ALL
echo php_value display_errors Off
) > .htaccess

echo.
echo ✅ Logging setup completed!
echo.
echo 📊 Log Files Created:
echo   📄 logs\application.log - General application errors
echo   🔒 logs\security.log - Security events ^(login, register, etc.^)
echo   💰 logs\business.log - Business events ^(orders, payments, etc.^)
echo   🐘 logs\php_errors.log - PHP specific errors
echo   🌐 logs\access.log - Access attempts ^(if configured^)
echo.
echo 🎯 Testing Instructions:
echo    1. Try to login with wrong credentials
echo    2. Register a new account  
echo    3. Place an order
echo    4. Check logs: type logs\security.log
echo    5. Check application logs: type logs\application.log
echo.
echo 🔄 Restart Apache to apply changes:
echo    1. XAMPP Control Panel ^Stop/Start Apache^
echo    2. Command line: httpd -k restart
echo.
pause