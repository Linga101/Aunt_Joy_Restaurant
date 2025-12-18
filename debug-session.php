<?php
/**
 * Debug Session Information
 * Shows current user session and role info
 */

require_once __DIR__ . '/config/db.php';

requireAuth();

$pageTitle = "Debug: Session Info";
$showNav = true;
$showFooter = true;
$bodyClass = 'debug-page';

include __DIR__ . '/views/templates/header.php';
?>

<div style="padding: 2rem; max-width: 800px; margin: 0 auto;">
    <h1>🔍 Session Debug Info</h1>
    
    <div style="background: #f5f5f5; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;">
        <h2>Session Variables</h2>
        <pre style="background: white; padding: 1rem; border-radius: 4px; overflow-x: auto;">
<?php 
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "Full Name: " . ($_SESSION['full_name'] ?? 'NOT SET') . "\n";
echo "Role ID: " . ($_SESSION['role_id'] ?? 'NOT SET') . "\n";
echo "Role Name: " . ($_SESSION['role_name'] ?? 'NOT SET') . "\n";
echo "\ngetCurrentUserRole(): " . getCurrentUserRole() . "\n";
echo "hasRole('Administrator'): " . (hasRole('Administrator') ? 'YES' : 'NO') . "\n";
echo "hasRole('Customer'): " . (hasRole('Customer') ? 'YES' : 'NO') . "\n";
?>
        </pre>
    </div>

    <div style="background: #f5f5f5; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;">
        <h2>Full Session Array</h2>
        <pre style="background: white; padding: 1rem; border-radius: 4px; overflow-x: auto;">
<?php print_r($_SESSION); ?>
        </pre>
    </div>

    <div style="background: #e3f2fd; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;">
        <h2>✅ Expected Behavior</h2>
        <ul style="margin: 0; padding-left: 1.5rem;">
            <li>If Role Name = "Administrator", you should see Categories Management in sidebar</li>
            <li>Quick Actions should show 4 buttons (Meals, Users, Categories, Settings)</li>
            <li>If anything is NULL or wrong, check the database query</li>
        </ul>
    </div>

    <div style="text-align: center; margin-top: 2rem;">
        <a href="/aunt_joy/views/admin/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
    </div>
</div>

<?php include __DIR__ . '/views/templates/footer.php'; ?>
