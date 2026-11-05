<?php
// api/get-washing_machines.php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../config/dbcon.php';

try {
    $result = $conn->query("SELECT machine_id, machine_name, type, status FROM washing_machines");
    $machines = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($machines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (mysqli_sql_exception $exception) {
    error_log('Failed to fetch washing machines: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load washing machines right now.']);
}