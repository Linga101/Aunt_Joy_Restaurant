<?php
/**
 * Get Report Controller
 * Generates sales reports and analytics
 */

require_once '../../config/db.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, null, 'Invalid request method');
}

// Check authentication and authorization
if (!isLoggedIn()) {
    jsonResponse(false, null, 'Unauthorized - Please log in');
}

if (!hasRole('Manager', 'Administrator')) {
    jsonResponse(false, null, 'Access denied - Manager role required');
}

$month = $_GET['month'] ?? null;
$year = $_GET['year'] ?? null;
$reportType = $_GET['type'] ?? 'summary';

if (!$month || !$year) {
    jsonResponse(false, null, 'Month and year are required');
}

try {
    $db = getDB();
    
    if ($reportType === 'summary') {
        // Generate monthly summary report
        
        // Get total revenue and orders
        $summaryStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as average_order_value,
                SUM(CASE WHEN order_status = 'Delivered' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN order_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_orders
            FROM orders
            WHERE MONTH(order_date) = :month 
            AND YEAR(order_date) = :year
        ");
        
        $summaryStmt->execute([
            'month' => $month,
            'year' => $year
        ]);
        
        $summary = $summaryStmt->fetch();
        
        // Get best selling items
        $bestSellersStmt = $db->prepare("
            SELECT 
                m.meal_id,
                m.meal_name,
                m.image_url,
                c.category_name,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.subtotal) as total_revenue,
                COUNT(DISTINCT o.order_id) as order_count
            FROM order_items oi
            INNER JOIN meals m ON oi.meal_id = m.meal_id
            INNER JOIN categories c ON m.category_id = c.category_id
            INNER JOIN orders o ON oi.order_id = o.order_id
            WHERE MONTH(o.order_date) = :month 
            AND YEAR(o.order_date) = :year
            AND o.order_status IN ('Delivered', 'Out for Delivery')
            GROUP BY m.meal_id
            ORDER BY total_quantity DESC
            LIMIT 10
        ");
        
        $bestSellersStmt->execute([
            'month' => $month,
            'year' => $year
        ]);
        
        $bestSellers = $bestSellersStmt->fetchAll();
        
        // Get daily sales
        $dailySalesStmt = $db->prepare("
            SELECT 
                DAY(order_date) as day,
                COUNT(*) as order_count,
                SUM(total_amount) as daily_revenue
            FROM orders
            WHERE MONTH(order_date) = :month 
            AND YEAR(order_date) = :year
            AND order_status = 'Delivered'
            GROUP BY DAY(order_date)
            ORDER BY day
        ");
        
        $dailySalesStmt->execute([
            'month' => $month,
            'year' => $year
        ]);
        
        $dailySales = $dailySalesStmt->fetchAll();
        
        // Get category breakdown
        $categoryStmt = $db->prepare("
            SELECT 
                c.category_name,
                COUNT(DISTINCT o.order_id) as order_count,
                SUM(oi.subtotal) as category_revenue,
                SUM(oi.quantity) as items_sold
            FROM order_items oi
            INNER JOIN meals m ON oi.meal_id = m.meal_id
            INNER JOIN categories c ON m.category_id = c.category_id
            INNER JOIN orders o ON oi.order_id = o.order_id
            WHERE MONTH(o.order_date) = :month 
            AND YEAR(o.order_date) = :year
            AND o.order_status = 'Delivered'
            GROUP BY c.category_id
            ORDER BY category_revenue DESC
        ");
        
        $categoryStmt->execute([
            'month' => $month,
            'year' => $year
        ]);
        
        $categoryBreakdown = $categoryStmt->fetchAll();
        
        // Format currency values
        $summary['total_revenue_formatted'] = formatCurrency($summary['total_revenue'] ?? 0);
        $summary['average_order_value_formatted'] = formatCurrency($summary['average_order_value'] ?? 0);
        
        foreach ($bestSellers as &$item) {
            $item['total_revenue_formatted'] = formatCurrency($item['total_revenue']);
        }
        
        foreach ($dailySales as &$day) {
            $day['daily_revenue_formatted'] = formatCurrency($day['daily_revenue']);
        }
        
        foreach ($categoryBreakdown as &$category) {
            $category['category_revenue_formatted'] = formatCurrency($category['category_revenue']);
        }
        
        $report = [
            'period' => [
                'month' => $month,
                'year' => $year,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1))
            ],
            'summary' => $summary,
            'best_sellers' => $bestSellers,
            'daily_sales' => $dailySales,
            'category_breakdown' => $categoryBreakdown
        ];
        
        jsonResponse(true, $report, 'Report generated successfully');
        
    } elseif ($reportType === 'detailed') {
        // Get detailed order list
        $ordersStmt = $db->prepare("
            SELECT 
                o.*,
                u.full_name as customer_name
            FROM orders o
            INNER JOIN users u ON o.customer_id = u.user_id
            WHERE MONTH(o.order_date) = :month 
            AND YEAR(o.order_date) = :year
            ORDER BY o.order_date DESC
        ");
        
        $ordersStmt->execute([
            'month' => $month,
            'year' => $year
        ]);
        
        $orders = $ordersStmt->fetchAll();
        
        foreach ($orders as &$order) {
            $order['total_amount_formatted'] = formatCurrency($order['total_amount']);
        }
        
        jsonResponse(true, $orders, 'Detailed report generated');
    }
    
} catch (PDOException $e) {
    jsonResponse(false, null, 'Failed to generate report: ' . $e->getMessage());
}
?>