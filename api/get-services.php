<?php
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../config/dbcon.php';

try {
    $result = $conn->query(
        "SELECT service_id, service_name, laundry_category, price, pricing_unit, description
         FROM services
         WHERE is_active = 1
         ORDER BY service_id ASC"
    );

    $services = $result->fetch_all(MYSQLI_ASSOC);

    foreach ($services as &$service) {
        $service['service_id'] = (int) $service['service_id'];
        $service['price'] = (float) $service['price'];
    }
    unset($service);

    echo json_encode($services, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (mysqli_sql_exception $exception) {
    error_log('Failed to fetch services: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load services right now.']);
}
