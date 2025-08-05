<?php
// Simple test file to check if PHP is working
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = array(
    'status' => 'success',
    'message' => 'PHP is working correctly',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'post_data' => $_POST,
    'get_data' => $_GET,
    'request_method' => $_SERVER['REQUEST_METHOD']
);

echo json_encode($response);
?>