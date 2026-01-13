<?php
/**
 * Client Error Logging Endpoint
 * Logs frontend errors for debugging
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/logger.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

// Get error data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['message'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid error data']));
}

// Log client error
logError("Frontend error", [
    'message' => $data['message'],
    'error' => $data['error'] ?? null,
    'url' => $data['url'] ?? null,
    'user_agent' => $data['userAgent'] ?? null,
    'timestamp' => $data['timestamp'] ?? date('Y-m-d H:i:s'),
    'stack_trace' => $data['stack'] ?? null,
    'context' => $data['context'] ?? []
]);

// Return success response
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Error logged successfully']);
?>