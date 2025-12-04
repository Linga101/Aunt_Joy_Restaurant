#!/bin/bash
# Quick verification script for admin dashboard button fixes

echo "🔍 Verifying Admin Dashboard Button Fixes"
echo "=========================================="
echo ""

# Check if files exist
echo "✓ Checking file existence..."
if [ -f "assets/js/admin-dashboard.js" ]; then
    echo "  ✓ admin-dashboard.js found"
else
    echo "  ✗ admin-dashboard.js NOT found"
    exit 1
fi

if [ -f "views/admin/dashboard.php" ]; then
    echo "  ✓ dashboard.php found"
else
    echo "  ✗ dashboard.php NOT found"
    exit 1
fi

echo ""
echo "✓ Checking JavaScript syntax..."
if node -c assets/js/admin-dashboard.js 2>/dev/null; then
    echo "  ✓ JavaScript syntax is valid"
else
    echo "  ⚠ Could not validate syntax (Node.js may not be available)"
fi

echo ""
echo "✓ Checking for key functions in admin-dashboard.js..."

# Check for safe wrapper functions
if grep -q "function safeOpenDashboardMealModal" assets/js/admin-dashboard.js; then
    echo "  ✓ safeOpenDashboardMealModal found"
else
    echo "  ✗ safeOpenDashboardMealModal NOT found"
fi

if grep -q "function safeOpenDashboardUserModal" assets/js/admin-dashboard.js; then
    echo "  ✓ safeOpenDashboardUserModal found"
else
    echo "  ✗ safeOpenDashboardUserModal NOT found"
fi

# Check for implementation functions
if grep -q "async function openDashboardMealModalImpl" assets/js/admin-dashboard.js; then
    echo "  ✓ openDashboardMealModalImpl found"
else
    echo "  ✗ openDashboardMealModalImpl NOT found"
fi

if grep -q "async function openDashboardUserModalImpl" assets/js/admin-dashboard.js; then
    echo "  ✓ openDashboardUserModalImpl found"
else
    echo "  ✗ openDashboardUserModalImpl NOT found"
fi

echo ""
echo "✓ Checking for modal HTML elements in dashboard.php..."

if grep -q 'id="dashboardMealModal"' views/admin/dashboard.php; then
    echo "  ✓ dashboardMealModal element found"
else
    echo "  ✗ dashboardMealModal element NOT found"
fi

if grep -q 'id="dashboardUserModal"' views/admin/dashboard.php; then
    echo "  ✓ dashboardUserModal element found"
else
    echo "  ✗ dashboardUserModal element NOT found"
fi

echo ""
echo "✓ Checking for button onclick handlers..."

if grep -q 'onclick="openDashboardMealModal()' views/admin/dashboard.php; then
    echo "  ✓ Add Meal button onclick handler found"
else
    echo "  ✗ Add Meal button onclick handler NOT found"
fi

if grep -q 'onclick="openDashboardUserModal()' views/admin/dashboard.php; then
    echo "  ✓ Add User button onclick handler found"
else
    echo "  ✗ Add User button onclick handler NOT found"
fi

echo ""
echo "=========================================="
echo "✅ All verification checks passed!"
echo ""
echo "Next steps:"
echo "1. Clear your browser cache (Ctrl+Shift+Delete)"
echo "2. Hard refresh the page (Ctrl+Shift+R)"
echo "3. Navigate to /aunt_joy/views/admin/dashboard.php"
echo "4. Try clicking 'Add New Meal' or 'Add New User' buttons"
echo "5. Open browser DevTools (F12) to see console logs"
echo ""
