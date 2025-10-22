<?php

/**
 * Example Alert Handlers
 * 
 * These are example implementations of alert handlers that can be
 * configured in the monitoring system to send notifications when
 * alerts are triggered.
 */

/**
 * Log alert to PHP error log
 */
function errorLogHandler(array $alert): void
{
    $message = sprintf(
        '[%s] %s - %s',
        strtoupper($alert['level']),
        $alert['datetime'],
        $alert['message']
    );
    error_log($message);
}

/**
 * Send alert via email
 */
function emailHandler(array $alert): void
{
    // Only send emails for critical alerts to avoid spam
    if ($alert['level'] !== 'critical') {
        return;
    }
    
    $to = 'admin@example.com';
    $subject = sprintf('[API ALERT] %s', $alert['message']);
    $body = sprintf(
        "Alert Level: %s\nTime: %s\nMessage: %s\n\nContext:\n%s",
        strtoupper($alert['level']),
        $alert['datetime'],
        $alert['message'],
        json_encode($alert['context'], JSON_PRETTY_PRINT)
    );
    
    mail($to, $subject, $body);
}

/**
 * Send alert to Slack webhook
 */
function slackHandler(array $alert): void
{
    $webhookUrl = 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL';
    
    $color = match($alert['level']) {
        'critical' => 'danger',
        'warning' => 'warning',
        default => 'good'
    };
    
    $payload = [
        'text' => 'API Alert',
        'attachments' => [
            [
                'color' => $color,
                'title' => $alert['message'],
                'text' => json_encode($alert['context'], JSON_PRETTY_PRINT),
                'footer' => 'API Monitor',
                'ts' => (int)$alert['timestamp']
            ]
        ]
    ];
    
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Send alert to Discord webhook
 */
function discordHandler(array $alert): void
{
    $webhookUrl = 'https://discord.com/api/webhooks/YOUR/WEBHOOK';
    
    $emoji = match($alert['level']) {
        'critical' => '🚨',
        'warning' => '⚠️',
        default => 'ℹ️'
    };
    
    $payload = [
        'embeds' => [
            [
                'title' => $emoji . ' ' . $alert['message'],
                'description' => '```json' . "\n" . json_encode($alert['context'], JSON_PRETTY_PRINT) . "\n" . '```',
                'color' => match($alert['level']) {
                    'critical' => 15158332, // Red
                    'warning' => 16776960,  // Yellow
                    default => 3447003     // Blue
                },
                'timestamp' => date('c', (int)$alert['timestamp']),
                'footer' => [
                    'text' => 'API Monitor'
                ]
            ]
        ]
    ];
    
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Send alert to Telegram
 */
function telegramHandler(array $alert): void
{
    $botToken = 'YOUR_BOT_TOKEN';
    $chatId = 'YOUR_CHAT_ID';
    
    $emoji = match($alert['level']) {
        'critical' => '🚨',
        'warning' => '⚠️',
        default => 'ℹ️'
    };
    
    $message = sprintf(
        "%s *API Alert*\n\n*Level:* %s\n*Time:* %s\n*Message:* %s\n\n```\n%s\n```",
        $emoji,
        strtoupper($alert['level']),
        $alert['datetime'],
        $alert['message'],
        json_encode($alert['context'], JSON_PRETTY_PRINT)
    );
    
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Write alert to custom file
 */
function fileHandler(array $alert): void
{
    $file = __DIR__ . '/../logs/critical_alerts.log';
    
    // Only log critical alerts to separate file
    if ($alert['level'] !== 'critical') {
        return;
    }
    
    $line = sprintf(
        "[%s] %s: %s | Context: %s\n",
        $alert['datetime'],
        strtoupper($alert['level']),
        $alert['message'],
        json_encode($alert['context'])
    );
    
    file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Send alert to PagerDuty
 */
function pagerDutyHandler(array $alert): void
{
    // Only send critical alerts to PagerDuty
    if ($alert['level'] !== 'critical') {
        return;
    }
    
    $integrationKey = 'YOUR_INTEGRATION_KEY';
    $url = 'https://events.pagerduty.com/v2/enqueue';
    
    $payload = [
        'routing_key' => $integrationKey,
        'event_action' => 'trigger',
        'payload' => [
            'summary' => $alert['message'],
            'severity' => 'critical',
            'source' => 'API Monitor',
            'timestamp' => date('c', (int)$alert['timestamp']),
            'custom_details' => $alert['context']
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Example configuration usage:
 * 
 * In config/api.php, add handlers:
 * 
 * 'monitoring' => [
 *     'enabled' => true,
 *     'alert_handlers' => [
 *         'errorLogHandler',    // Always log to error log
 *         'emailHandler',       // Email for critical alerts
 *         'slackHandler',       // Send to Slack
 *         // 'discordHandler',  // Or Discord
 *         // 'telegramHandler', // Or Telegram
 *         // 'pagerDutyHandler', // Or PagerDuty
 *     ],
 *     // ... other config
 * ],
 */
