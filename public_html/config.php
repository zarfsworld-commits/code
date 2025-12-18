<?php
/**
 * Form Submission Configuration
 * 
 * Configure submission delivery options
 */

return [
    // Delivery methods (both can be active simultaneously)
    'send_methods' => [
        'telegram' => true,  // true = active, false = inactive
        'email' => true,     // true = active, false = inactive
    ],

    // Telegram Settings
    'telegram' => [
        'bot_token' => '8374391283:AAG9p-pDlqekhDrsDMOniX6ujqJR1T_2JKo',  // Telegram bot token
        'chat_id' => '-1003122979707',      // Chat/channel/group ID
    ],

    // Email Settings
    'email' => [
        'to' => 'your-email@example.com',      // Destination email
        'from' => 'noreply@damacisland.com',   // From email
        'from_name' => 'DAMAC Islands',        // Sender name
        'subject' => 'New Lead - DAMAC Islands', // Email subject
        
        // SMTP Settings (optional - leave empty to use default mail())
        'smtp' => [
            'enabled' => false,           // true to use SMTP, false for mail()
            'host' => 'smtp.example.com', // SMTP server
            'port' => 587,                // SMTP port (587 for TLS, 465 for SSL)
            'username' => '',             // SMTP username
            'password' => '',             // SMTP password
            'encryption' => 'tls',        // 'tls' or 'ssl'
        ],
    ],

    // General Settings
    'general' => [
        'log_submissions' => true,  // Save submission logs
        'log_file' => __DIR__ . '/logs/submissions.log',
        'timezone' => 'Asia/Dubai', // Timezone
    ],
];
