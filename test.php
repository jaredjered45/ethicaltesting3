<?php
header('Content-Type: application/json');

// Basic PHP test
$response = array(
    'status' => 'success',
    'message' => 'PHP is working correctly',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'server_info' => $_SERVER['SERVER_SOFTWARE'],
    'tests' => array()
);

// Test 1: Configuration file
try {
    require_once 'config.php';
    $response['tests']['config'] = array(
        'status' => 'pass',
        'message' => 'Configuration file loaded successfully'
    );
} catch (Exception $e) {
    $response['tests']['config'] = array(
        'status' => 'fail',
        'message' => 'Configuration error: ' . $e->getMessage()
    );
}

// Test 2: PHPMailer classes
try {
    require_once 'class.phpmailer.php';
    require_once 'class.smtp.php';
    
    $mail = new PHPMailer();
    $smtp = new SMTP();
    
    $response['tests']['phpmailer'] = array(
        'status' => 'pass',
        'message' => 'PHPMailer classes loaded successfully'
    );
} catch (Exception $e) {
    $response['tests']['phpmailer'] = array(
        'status' => 'fail',
        'message' => 'PHPMailer error: ' . $e->getMessage()
    );
}

// Test 3: Required PHP functions
$required_functions = array('fsockopen', 'curl_init', 'mail', 'json_encode', 'json_decode');
$missing_functions = array();

foreach ($required_functions as $func) {
    if (!function_exists($func)) {
        $missing_functions[] = $func;
    }
}

if (empty($missing_functions)) {
    $response['tests']['php_functions'] = array(
        'status' => 'pass',
        'message' => 'All required PHP functions are available'
    );
} else {
    $response['tests']['php_functions'] = array(
        'status' => 'fail',
        'message' => 'Missing PHP functions: ' . implode(', ', $missing_functions)
    );
}

// Test 4: File permissions
$test_files = array('SS-Or.txt');
foreach ($test_files as $file) {
    if (file_exists($file)) {
        if (is_writable($file)) {
            $response['tests']['file_permissions_' . $file] = array(
                'status' => 'pass',
                'message' => $file . ' is writable'
            );
        } else {
            $response['tests']['file_permissions_' . $file] = array(
                'status' => 'fail',
                'message' => $file . ' is not writable'
            );
        }
    } else {
        // Try to create the file
        $fp = @fopen($file, 'a');
        if ($fp) {
            fclose($fp);
            $response['tests']['file_permissions_' . $file] = array(
                'status' => 'pass',
                'message' => $file . ' can be created and is writable'
            );
        } else {
            $response['tests']['file_permissions_' . $file] = array(
                'status' => 'fail',
                'message' => 'Cannot create or write to ' . $file
            );
        }
    }
}

// Test 5: Network connectivity (basic test)
$test_url = 'http://www.geoplugin.net/json.gp?ip=8.8.8.8';
$context = stream_context_create(array(
    'http' => array(
        'timeout' => 5
    )
));

$result = @file_get_contents($test_url, false, $context);
if ($result !== false) {
    $response['tests']['network'] = array(
        'status' => 'pass',
        'message' => 'Network connectivity test passed'
    );
} else {
    $response['tests']['network'] = array(
        'status' => 'warning',
        'message' => 'Network connectivity test failed - GeoIP lookup may not work'
    );
}

// Overall status
$failed_tests = 0;
$warning_tests = 0;
foreach ($response['tests'] as $test) {
    if ($test['status'] == 'fail') {
        $failed_tests++;
    } elseif ($test['status'] == 'warning') {
        $warning_tests++;
    }
}

if ($failed_tests > 0) {
    $response['status'] = 'error';
    $response['message'] = $failed_tests . ' test(s) failed';
} elseif ($warning_tests > 0) {
    $response['status'] = 'warning';
    $response['message'] = $warning_tests . ' test(s) have warnings';
} else {
    $response['status'] = 'success';
    $response['message'] = 'All tests passed - System is ready';
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>