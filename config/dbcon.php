<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'lavadera_db';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('The service is temporarily unavailable. Please try again later.');
}

function createNotification($conn, $userId, $orderId, $type, $title, $message) {
    // 1. Check customer preferences
    $prefStmt = $conn->prepare("SELECT order_updates, promotions FROM notification_preferences WHERE user_id = ?");
    $prefStmt->bind_param("i", $userId);
    $prefStmt->execute();
    $prefRes = $prefStmt->get_result()->fetch_assoc();
    $prefStmt->close();

    // Default to enabled if preference row does not exist yet
    $orderUpdatesAllowed = $prefRes ? intval($prefRes['order_updates']) : 1;
    $promosAllowed = $prefRes ? intval($prefRes['promotions']) : 1;

    // Filter by type
    if ($type === 'promo' && !$promosAllowed) return false;
    if ($type !== 'promo' && !$orderUpdatesAllowed) return false;

    // 2. Insert notification record
    $insStmt = $conn->prepare("
        INSERT INTO notifications (user_id, order_id, type, title, message, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, 0, NOW())
    ");
    $insStmt->bind_param("iisss", $userId, $orderId, $type, $title, $message);
    $success = $insStmt->execute();
    $insStmt->close();

    return $success;
}