#!/bin/bash

echo "🚀 Setting up Aunt Joy's Restaurant project..."

# Create directory structure
mkdir -p config
mkdir -p controllers/{auth,customer,admin,sales,manager}
mkdir -p views/{templates,auth,customer,admin,sales,manager}
mkdir -p assets/{css,js,images/meals}
mkdir -p database

echo "✅ Directory structure ensured"

# Set sane permissions
chmod -R 755 .
chmod -R 775 assets/images/meals

echo "✅ Permissions updated (images folder writable)"

# Ask to import schema
read -p "Import MySQL schema from database/schema.sql? (y/n): " import_choice
if [[ "$import_choice" == "y" || "$import_choice" == "Y" ]]; then
    read -p "MySQL username: " mysql_user
    read -s -p "MySQL password: " mysql_pass
    echo
    if mysql -u "$mysql_user" -p"$mysql_pass" < database/schema.sql; then
        echo "✅ Database schema imported successfully"
    else
        echo "❌ Failed to import database schema" >&2
    fi
fi

echo "🎉 Setup complete. Visit http://localhost/Aunt_Joy_Restaurant/ to get started."


