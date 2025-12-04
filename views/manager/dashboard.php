<?php
require_once __DIR__ . '/../../config/db.php';
requireAuth();
requireRole('Manager', 'Administrator');

$pageTitle = "Manager Dashboard - Aunt Joy's Restaurant";
$customCSS = "dashboard.css";
$showNav = true;
$showFooter = true;
$bodyClass = "dashboard-page";

include '../templates/header.php';
?>

<div class="dashboard-layout">
    <?php include '../templates/sidebar.php'; ?>
    
    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Sales Reports & Analytics</h1>
                <p>📅 Today: <?php echo date('l, F j, Y'); ?></p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="generateReport()">
                    🔄 Refresh
                </button>
            </div>
        </div>

        <!-- Report Filter -->
        <div class="card">
            <div class="card-header">
                <h2>📅 Report Period</h2>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Month</label>
                        <select id="monthSelect" class="form-control">
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Year</label>
                        <select id="yearSelect" class="form-control">
                            <option value="2023">2023</option>
                            <option value="2024">2024</option>
                            <option value="2025" selected>2025</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary btn-block" onclick="generateReport()">
                            📊 Generate Report
                        </button>
                    </div>

                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="export-buttons">
                            <button class="btn-export pdf" onclick="exportPDF()">
                                📄 PDF
                            </button>
                            <button class="btn-export excel" onclick="exportExcel()">
                                📊 Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <h3 id="totalRevenue">MK 0</h3>
                    <p>Total Revenue</p>
                    <span class="stat-trend trend-up">↑ 15%</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-content">
                    <h3 id="totalOrders">0</h3>
                    <p>Total Orders</p>
                    <span class="stat-trend trend-up">↑ 8%</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <h3 id="avgOrderValue">MK 0</h3>
                    <p>Avg Order Value</p>
                    <span class="stat-trend trend-up">↑ 12%</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-content">
                    <h3 id="bestSeller">-</h3>
                    <p>Best Selling Item</p>
                    <span class="stat-subtitle" id="bestSellerCount">0 orders</span>
                </div>
            </div>
        </div>

        <!-- Best Sellers Table -->
        <div class="card">
            <div class="card-header">
                <h2>🏆 Top Selling Items</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                                <th>Popularity</th>
                            </tr>
                        </thead>
                        <tbody id="bestSellersTable">
                            <tr>
                                <td colspan="6" class="text-center">Loading data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Daily Sales Chart -->
        <div class="card">
            <div class="card-header">
                <h2>📈 Daily Sales Trend</h2>
            </div>
            <div class="card-body">
                <div id="dailySalesChart" class="chart-container">
                    <p class="text-muted text-center">Chart will be displayed here</p>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
let currentMonth = new Date().getMonth() + 1;
let currentYear = new Date().getFullYear();

// Set current month/year on load
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('monthSelect').value = currentMonth;
    document.getElementById('yearSelect').value = currentYear;
    generateReport();
    
    // Auto-refresh report every 30 seconds
    setInterval(generateReport, 30000);
});

// Listen for visibility change to pause/resume polling when tab is not active
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        console.log('Tab inactive - pausing report updates');
    } else {
        console.log('Tab active - resuming report updates');
        generateReport();
    }
});

// Generate report
async function generateReport() {
    const month = document.getElementById('monthSelect').value;
    const year = document.getElementById('yearSelect').value;
    
    currentMonth = month;
    currentYear = year;
    
    try {
        const response = await fetch(`/aunt_joy/controllers/manager/get_report.php?month=${month}&year=${year}&type=summary`);
        const result = await response.json();
        
        if (result.success) {
            displayReport(result.data);
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Error generating report:', error);
        showNotification('Failed to generate report', 'error');
    }
}

// Display report
function displayReport(data) {
    // Update stats
    const summary = data.summary;
    document.getElementById('totalRevenue').textContent = summary.total_revenue_formatted || 'MK 0';
    document.getElementById('totalOrders').textContent = summary.total_orders || '0';
    document.getElementById('avgOrderValue').textContent = summary.average_order_value_formatted || 'MK 0';
    
    // Best seller
    if (data.best_sellers && data.best_sellers.length > 0) {
        const best = data.best_sellers[0];
        document.getElementById('bestSeller').textContent = best.meal_name;
        document.getElementById('bestSellerCount').textContent = `${best.total_quantity} sold`;
    }
    
    // Render best sellers table
    renderBestSellers(data.best_sellers || []);
    
    // Render daily sales chart
    renderDailySalesChart(data.daily_sales || []);
}

// Render best sellers
function renderBestSellers(bestSellers) {
    const tbody = document.getElementById('bestSellersTable');
    
    if (bestSellers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No data available</td></tr>';
        return;
    }
    
    const maxQuantity = Math.max(...bestSellers.map(item => item.total_quantity));
    
    tbody.innerHTML = bestSellers.map((item, index) => {
        const rankClass = index === 0 ? 'gold' : index === 1 ? 'silver' : index === 2 ? 'bronze' : '';
        const popularity = (item.total_quantity / maxQuantity) * 100;
        
        return `
            <tr>
                <td>
                    <div class="rank-badge ${rankClass}">${index + 1}</div>
                </td>
                <td>
                    <div class="item-info">
                        <span class="item-emoji">${item.image_url || '🍽️'}</span>
                        <strong>${item.meal_name}</strong>
                    </div>
                </td>
                <td>${item.category_name}</td>
                <td><strong>${item.total_quantity}</strong></td>
                <td><strong>${item.total_revenue_formatted}</strong></td>
                <td>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${popularity}%"></div>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// Render daily sales chart (simple bar chart)
function renderDailySalesChart(dailySales) {
    const container = document.getElementById('dailySalesChart');
    
    if (dailySales.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">No sales data available</p>';
        return;
    }
    
    const maxRevenue = Math.max(...dailySales.map(d => parseFloat(d.daily_revenue)));
    
    container.innerHTML = `
        <div class="bar-chart">
            ${dailySales.map(day => {
                const height = (parseFloat(day.daily_revenue) / maxRevenue) * 100;
                return `
                    <div class="bar-wrapper">
                        <div class="bar-value">${day.daily_revenue_formatted}</div>
                        <div class="bar" style="height: ${height}%"></div>
                        <div class="bar-label">Day ${day.day}</div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

// Export to PDF
function exportPDF() {
    const month = document.getElementById('monthSelect').value;
    const year = document.getElementById('yearSelect').value;
    
    showNotification('Generating PDF report...', 'info');
    
    window.open(`/aunt_joy/controllers/manager/export_pdf.php?month=${month}&year=${year}`, '_blank');
}

// Export to Excel
function exportExcel() {
    const month = document.getElementById('monthSelect').value;
    const year = document.getElementById('yearSelect').value;
    
    showNotification('Generating Excel report...', 'info');
    
    window.open(`/aunt_joy/controllers/manager/export_excel.php?month=${month}&year=${year}`, '_blank');
}
</script>

<?php include '../templates/footer.php'; ?>