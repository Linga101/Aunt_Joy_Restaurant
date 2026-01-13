<?php
/**
 * API Diagnostic Test
 * Tests all category API endpoints and shows what's working/failing
 */

require_once __DIR__ . '/config/db.php';

requireAuth();

$pageTitle = "API Diagnostic - Category Endpoints";
$showNav = true;
$showFooter = true;
$bodyClass = 'debug-page';

include __DIR__ . '/views/templates/header.php';
?>

<div style="padding: 2rem; max-width: 1200px; margin: 0 auto;">
    <h1>🔧 API Diagnostic Test</h1>
    
    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 1rem; border-radius: 8px; margin: 1rem 0;">
        <strong>⚠️ Instructions:</strong>
        <ol>
            <li>Open your Browser DevTools (F12)</li>
            <li>Go to Network tab</li>
            <li>Click each test button below</li>
            <li>Watch the Network tab for the response</li>
            <li>If you see error, copy the Response text and send it</li>
        </ol>
    </div>

    <div style="background: #f5f5f5; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;">
        <h2>Session Info</h2>
        <p><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'NOT SET'; ?></p>
        <p><strong>Username:</strong> <?php echo $_SESSION['username'] ?? 'NOT SET'; ?></p>
        <p><strong>Role:</strong> <?php echo $_SESSION['role_name'] ?? 'NOT SET'; ?></p>
        <p><strong>isLoggedIn():</strong> <?php echo isLoggedIn() ? '✅ YES' : '❌ NO'; ?></p>
        <p><strong>hasRole('Administrator'):</strong> <?php echo hasRole('Administrator') ? '✅ YES' : '❌ NO'; ?></p>
    </div>

    <div style="background: #f5f5f5; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;">
        <h2>Test API Endpoints</h2>
        
        <div style="margin: 1rem 0;">
            <button onclick="testAPI('admin/get_categories.php', 'GET')" class="btn btn-primary">
                Test: GET /admin/get_categories.php
            </button>
            <small style="display: block; margin-top: 0.5rem; color: #666;">
                Should return JSON with categories array
            </small>
        </div>

        <div style="margin: 1rem 0;">
            <button onclick="testAPI('admin/get_users.php', 'GET')" class="btn btn-primary">
                Test: GET /admin/get_users.php (for comparison)
            </button>
            <small style="display: block; margin-top: 0.5rem; color: #666;">
                Should return JSON with users array (this works, right?)
            </small>
        </div>

        <div style="margin: 1rem 0;">
            <button onclick="testSaveCategory()" class="btn btn-primary">
                Test: POST /admin/save_category.php (Create Test Category)
            </button>
            <small style="display: block; margin-top: 0.5rem; color: #666;">
                Should create a test category
            </small>
        </div>
    </div>

    <div style="background: #e3f2fd; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;">
        <h2>Response History</h2>
        <div id="responseLog" style="background: white; padding: 1rem; border-radius: 4px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 0.9rem;">
            <p style="color: #999;">API responses will appear here...</p>
        </div>
    </div>

    <div style="text-align: center; margin-top: 2rem;">
        <a href="/Aunt_Joy_Restaurant/views/admin/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

<script>
async function testAPI(endpoint, method) {
    const url = `/Aunt_Joy_Restaurant/controllers/${endpoint}`;
    const logDiv = document.getElementById('responseLog');
    
    logDiv.innerHTML += `<p><strong>Testing ${method} ${endpoint}...</strong></p>`;
    
    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin'
        });

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            data = { raw: text };
        }

        const logEntry = document.createElement('pre');
        logEntry.style.cssText = 'background: #f9f9f9; padding: 1rem; margin: 0.5rem 0; border-left: 3px solid ' + (response.ok ? '#4caf50' : '#f44336') + ';';
        logEntry.textContent = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
        logDiv.appendChild(logEntry);
        logDiv.scrollTop = logDiv.scrollHeight;
    } catch (error) {
        const logEntry = document.createElement('pre');
        logEntry.style.cssText = 'background: #ffebee; padding: 1rem; margin: 0.5rem 0; border-left: 3px solid #f44336; color: #d32f2f;';
        logEntry.textContent = 'Error: ' + error.message;
        logDiv.appendChild(logEntry);
        logDiv.scrollTop = logDiv.scrollHeight;
    }
}

async function testSaveCategory() {
    const url = `/Aunt_Joy_Restaurant/controllers/admin/save_category.php`;
    const logDiv = document.getElementById('responseLog');
    
    logDiv.innerHTML += `<p><strong>Testing POST to save_category.php...</strong></p>`;
    
    const payload = {
        category_id: null,
        category_name: 'Test Category ' + Date.now(),
        description: 'Created by API test at ' + new Date().toISOString(),
        display_order: 99,
        is_active: true
    };
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        const logEntry = document.createElement('pre');
        logEntry.style.cssText = 'background: #f9f9f9; padding: 1rem; margin: 0.5rem 0; border-left: 3px solid ' + (response.ok ? '#4caf50' : '#f44336') + ';';
        logEntry.textContent = 'Status: ' + response.status + '\n\n' + JSON.stringify(data, null, 2);
        logDiv.appendChild(logEntry);
        logDiv.scrollTop = logDiv.scrollHeight;
    } catch (error) {
        const logEntry = document.createElement('pre');
        logEntry.style.cssText = 'background: #ffebee; padding: 1rem; margin: 0.5rem 0; border-left: 3px solid #f44336; color: #d32f2f;';
        logEntry.textContent = 'Error: ' + error.message;
        logDiv.appendChild(logEntry);
        logDiv.scrollTop = logDiv.scrollHeight;
    }
}
</script>

<?php include __DIR__ . '/views/templates/footer.php'; ?>

