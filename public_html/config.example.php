<?php
/**
 * Configuration File - EXAMPLE
 * 
 * Copy this file to "config.php" and configure with your real data
 */

return [
    // Delivery methods (both can be active simultaneously)
    'send_methods' => [
        'telegram' => true,  // true = active, false = inactive
        'email' => true,     // true = active, false = inactive
    ],

    // Telegram Settings
    'telegram' => [
        // IMPORTANT: Replace with your real data!
        // 1. Create a bot at @BotFather on Telegram
        // 2. Copy the received token
        'bot_token' => '123456789:ABCdefGHIjklMNOpqrsTUVwxyz',
        
        // 3. Get chat_id by accessing:
        // https://api.telegram.org/bot<YOUR_TOKEN>/getUpdates
        // (after sending a message to the bot)
        'chat_id' => '123456789',  // Positive number for user, negative for group
    ],

    // Email Settings
    'email' => [
        'to' => 'your-email@example.com',          // CHANGE: Email that will receive leads
        'from' => 'noreply@damacisland.com',       // From email
        'from_name' => 'DAMAC Islands',            // Sender name
        'subject' => 'New Lead - DAMAC Islands',   // Email subject
        
        // SMTP Settings (optional - leave empty to use default mail())
        'smtp' => [
            'enabled' => false,                 // true to use SMTP, false for mail()
            'host' => 'smtp.gmail.com',         // SMTP server
            'port' => 587,                      // Port (587 for TLS, 465 for SSL)
            'username' => 'your-email@gmail.com',// SMTP username
            'password' => 'your-app-password',   // SMTP password (use Gmail app password)
            'encryption' => 'tls',              // 'tls' or 'ssl'
        ],
    ],

    // General Settings
    'general' => [
        'log_submissions' => true,  // Save submission logs
        'log_file' => __DIR__ . '/logs/submissions.log',
        'timezone' => 'Asia/Dubai', // Timezone (Dubai)
    ],
];
