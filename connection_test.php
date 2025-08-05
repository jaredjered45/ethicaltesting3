<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$response = array(
    'status' => 'success',
    'message' => 'PHP connection is working',
    'method' => $_SERVER['REQUEST_METHOD'],
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion()
);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $response['post_data'] = $_POST;
    $response['message'] = 'POST request received successfully';
}

echo json_encode($response);
?>