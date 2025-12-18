<?php
/**
 * Form Processor - DAMAC Islands
 * 
 * Receives form data and sends via Telegram and/or Email
 */

// Disable all error reporting except fatal errors
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Load configuration
$config = require_once __DIR__ . '/config.php';

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/include/phpmailer/src/Exception.php';
require __DIR__ . '/include/phpmailer/src/PHPMailer.php';
require __DIR__ . '/include/phpmailer/src/SMTP.php';

// Set timezone
date_default_timezone_set($config['general']['timezone']);

// Function to log messages
function logMessage($message, $config) {
    if ($config['general']['log_submissions']) {
        $logDir = dirname($config['general']['log_file']);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}\n";
        file_put_contents($config['general']['log_file'], $logEntry, FILE_APPEND);
    }
}

// Function to send message to Telegram
function sendToTelegram($data, $config) {
    if (!$config['send_methods']['telegram']) {
        return ['success' => true, 'message' => 'Telegram disabled'];
    }

    $botToken = $config['telegram']['bot_token'];
    $chatId = $config['telegram']['chat_id'];

    if (empty($botToken) || empty($chatId)) {
        return ['success' => false, 'message' => 'Incomplete Telegram configuration'];
    }

    // Format message
    $message = "🏝️ *NEW LEAD - DAMAC ISLANDS*\n\n";
    
    // Fields to exclude from message
    $excludeFields = ['lpsSubmissionConfig', 'thank-you-message', 'thank-you-message-timeout', 'download_file'];
    
    foreach ($data as $key => $value) {
        if (!empty($value) && !is_array($value) && !in_array($key, $excludeFields)) {
            $key = str_replace('_', ' ', $key);
            $key = ucwords($key);
            $message .= "*{$key}:* {$value}\n";
        }
    }

    // Send to Telegram
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    
    $postData = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];

    // Use file_get_contents as fallback if cURL is not available
    if (function_exists('curl_init')) {
        // Use cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        // Use file_get_contents
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($postData),
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        $context  = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        
        // Get HTTP response code
        $httpCode = 500;
        if (isset($http_response_header[0])) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $match);
            $httpCode = isset($match[1]) ? (int)$match[1] : 500;
        }
        if ($response !== false) {
            $httpCode = 200;
        }
    }

    if ($httpCode == 200) {
        return ['success' => true, 'message' => 'Sent to Telegram successfully'];
    } else {
        return ['success' => false, 'message' => 'Error sending to Telegram', 'response' => $response];
    }
}

// Function to send email
function sendEmail($data, $config) {
    if (!$config['send_methods']['email']) {
        return ['success' => true, 'message' => 'Email disabled'];
    }

    $to = $config['email']['to'];
    $subject = $config['email']['subject'];
    $fromEmail = $config['email']['from'];
    $fromName = $config['email']['from_name'];

    // Create HTML email body
    $emailBody = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; }
            .field { margin-bottom: 15px; }
            .field-label { font-weight: bold; color: #667eea; }
            .field-value { margin-top: 5px; padding: 10px; background: white; border-left: 3px solid #667eea; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🏝️ New Lead - DAMAC Islands</h1>
            </div>
            <div class="content">';

    // Fields to exclude from email
    $excludeFields = ['lpsSubmissionConfig', 'thank-you-message', 'thank-you-message-timeout', 'download_file', 'ip_address', 'user_agent', 'referer'];

    foreach ($data as $key => $value) {
        if (!empty($value) && !is_array($value) && !in_array($key, $excludeFields)) {
            $key = str_replace('_', ' ', $key);
            $key = ucwords($key);
            $value = htmlspecialchars($value);
            
            $emailBody .= "
                <div class='field'>
                    <div class='field-label'>{$key}</div>
                    <div class='field-value'>{$value}</div>
                </div>";
        }
    }

    $emailBody .= "
                <div class='field'>
                    <div class='field-label'>Submission Date</div>
                    <div class='field-value'>" . date('d/m/Y H:i:s') . "</div>
                </div>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " DAMAC Properties. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";

    // Email headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "Reply-To: {$fromEmail}\r\n";

    // Send using SMTP or mail()
    if ($config['email']['smtp']['enabled']) {
        // Use SMTP (requires PHPMailer or similar)
        return sendEmailSMTP($to, $subject, $emailBody, $headers, $config);
    } else {
        // Use default PHP mail()
        if (mail($to, $subject, $emailBody, $headers)) {
            return ['success' => true, 'message' => 'Email sent successfully'];
        } else {
            return ['success' => false, 'message' => 'Error sending email'];
        }
    }
}

// Function to send email via SMTP (optional)
function sendEmailSMTP($to, $subject, $body, $headers, $config) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $config['email']['smtp']['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['email']['smtp']['username'];
        $mail->Password   = $config['email']['smtp']['password'];
        $mail->SMTPSecure = $config['email']['smtp']['encryption'];
        $mail->Port       = $config['email']['smtp']['port'];
        $mail->CharSet    = 'UTF-8';
        
        // Recipients
        $mail->setFrom($config['email']['from'], $config['email']['from_name']);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully via SMTP'];
    } catch (\Exception $e) {
        return ['success' => false, 'message' => 'SMTP Error: ' . $mail->ErrorInfo];
    }
}

// Process request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $formData = [];
        
        // Check if data is in fields array format
        if (isset($_POST['fields']) && is_array($_POST['fields'])) {
            // Process fields array format (fields[0][name], fields[0][value], etc.)
            foreach ($_POST['fields'] as $field) {
                if (isset($field['name']) && isset($field['value'])) {
                    $fieldName = strip_tags(trim($field['name']));
                    $fieldValue = strip_tags(trim($field['value']));
                    
                    // Only include fields[0] to fields[4]
                    $formData[$fieldName] = $fieldValue;
                }
            }
        } else {
            // Process standard POST format
            foreach ($_POST as $key => $value) {
                // Handle arrays (like select fields)
                if (is_array($value)) {
                    $formData[$key] = strip_tags(trim(implode(', ', $value)));
                } else {
                    $formData[$key] = strip_tags(trim($value));
                }
            }
        }

        // Add extra information
        $formData['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $formData['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $formData['referer'] = $_SERVER['HTTP_REFERER'] ?? 'Direct';

        // Validate required data
        if (empty($formData['Email']) && empty($formData['Phone'])) {
            throw new Exception('Email or phone are required');
        }

        $results = [];

        // Send to Telegram (don't fail on error)
        $telegramResult = sendToTelegram($formData, $config);
        $results['telegram'] = $telegramResult;
        
        if ($telegramResult['success']) {
            logMessage("Telegram: " . $telegramResult['message'], $config);
        } else {
            logMessage("ERROR - Telegram: " . $telegramResult['message'], $config);
        }

        // Send by Email (don't fail on error)
        $emailResult = sendEmail($formData, $config);
        $results['email'] = $emailResult;
        
        if ($emailResult['success']) {
            logMessage("Email: " . $emailResult['message'], $config);
        } else {
            logMessage("ERROR - Email: " . $emailResult['message'], $config);
        }

        // Log received data
        logMessage("New form received: " . json_encode($formData), $config);

        // Always return success to user
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Form submitted successfully'
        ]);

    } catch (Exception $e) {
        logMessage("ERROR - Validation: " . $e->getMessage(), $config);
        
        // Still return success to user
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Form submitted successfully'
        ]);
    }
} else {
    // Still return success even for wrong method
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Form submitted successfully'
    ]);
}
