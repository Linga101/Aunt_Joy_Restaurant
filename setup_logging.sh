#!/bin/bash

# Aunt Joy's Restaurant - Logging Setup Script
# Sets up proper logging configuration and directories

echo "📝 Aunt Joy's Restaurant - Logging Setup"
echo "=========================================="

# Create logs directory
echo "📁 Creating logs directory..."
mkdir -p logs

# Set proper permissions
echo "🔐 Setting permissions..."
chmod 755 logs
chmod 666 logs/*.log 2>/dev/null

# Create log files
echo "📄 Creating log files..."
touch logs/application.log
touch logs/security.log
touch logs/business.log
touch logs/php_errors.log
touch logs/access.log

# Set proper permissions to log files
chmod 666 logs/*.log

# Create .htaccess for logging
echo "⚙️ Creating logging configuration..."
cat > logs/.htaccess << 'EOF'
# Restrict access to log files
<Files "*.log">
    Require ip denied
    Order deny,allow
    Deny from all
</Files>

# PHP Configuration
echo "🐘 Setting up PHP logging configuration..."
cat > .htaccess << 'EOF'
# PHP Error Logging
php_value log_errors On
php_value error_log "/xampp/htdocs/aunt_joy/logs/php_errors.log"
php_value error_reporting E_ALL
php_value display_errors Off

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
EOF

echo ""
echo "✅ Logging setup completed!"
echo ""
echo "📊 Log Files Created:"
echo "  📄 logs/application.log - General application errors"
echo "  🔒 logs/security.log - Security events (login, register, etc.)"
echo "  💰 logs/business.log - Business events (orders, payments, etc.)"
echo "  🐘 logs/php_errors.log - PHP specific errors"
echo "  🌐 logs/access.log - Access attempts (if configured)"
echo ""
echo "🎯 Logging Levels:"
echo "  [ERROR] - Database errors, authentication failures"
echo "  [SECURITY] - Login attempts, registrations, user actions"
echo "  [BUSINESS] - Order placement, payments, cart actions"
echo "  [INFO] - General application events"
echo ""
echo "🔍 Viewing Logs:"
echo "  Tail application log: tail -f logs/application.log"
echo "  Tail security log: tail -f logs/security.log"
echo "  Tail business log: tail -f logs/business.log"
echo ""
echo "📱 Test logging by:"
echo "  1. Try to login with wrong credentials"
echo "  2. Register a new account"
echo "  3. Place an order"
echo "  4. Check logs: tail -f logs/security.log"
echo ""
echo "🌐 Restart Apache to apply .htaccess changes"
echo "  sudo service apache2 restart  (Ubuntu/Debian)"
echo "  sudo systemctl restart apache2  (systemd)"
echo "  sudo service httpd restart (RHEL/CentOS)"
echo "  sudo /opt/lampp/lampp restart"