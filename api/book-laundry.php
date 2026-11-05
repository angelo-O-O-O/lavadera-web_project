<?php
// api/book-laundry.php
session_start();
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../config/dbcon.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $serviceId = $_POST['service_id'] ?? 0;
    $weight = $_POST['weight'] ?? 0;

    if (!$serviceId || $weight <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid service selection or weight.']);
        exit;
    }

    try {
        // Fetch service price
        $stmt = $conn->prepare("SELECT price FROM services WHERE service_id = ?");
        $stmt->bind_param("i", $serviceId);
        $stmt->execute();
        $service = $stmt->get_result()->fetch_assoc();

        if (!$service) {
            echo json_encode(['success' => false, 'message' => 'Service not found.']);
            exit;
        }

        // Calculate total price and tracking number
        $totalPrice = $service['price'] * $weight;
        $trackingNo = 'LAV-' . strtoupper(substr(uniqid(), -6));

        // Insert order
        $insert = $conn->prepare("INSERT INTO laundry_orders (tracking_no, user_id, service_id, weight_load, total_price) VALUES (?, ?, ?, ?, ?)");
        $insert->bind_param("siidd", $trackingNo, $userId, $serviceId, $weight, $totalPrice);
        $insert->execute();

        echo json_encode([
            'success' => true,
            'tracking_no' => $trackingNo,
            'message' => 'Laundry booked successfully!'
        ]);

    } catch (Exception $e) {
        error_log('Booking Error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}