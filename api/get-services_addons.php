<?php
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../config/dbcon.php';

try {
    $result = $conn->query(
        "SELECT addon_id, addon_name, price
         FROM service_addons
         WHERE is_active = 1
         ORDER BY addon_id ASC"
    );

    $addons = $result->fetch_all(MYSQLI_ASSOC);

    foreach ($addons as &$addon) {
        $addon['addon_id'] = (int) $addon['addon_id'];
        $addon['price'] = (float) $addon['price'];
    }
    unset($addon);

    echo json_encode($addons, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (mysqli_sql_exception $exception) {
    error_log('Failed to fetch service add-ons: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load service add-ons right now.']);
}
