<?php

/**
 * Health Check Endpoint
 * 
 * Provides real-time health status of the API
 * Can be used by load balancers, monitoring tools, etc.
 * 
 * Usage:
 * GET /health.php
 * GET /health.php?format=prometheus (for Prometheus scraping)
 * GET /health.php?format=json (default)
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Observability\Monitor;

// Load configuration
$config = require __DIR__ . '/config/api.php';
$monitorConfig = $config['monitoring'] ?? [];

// Initialize monitor
$monitor = new Monitor($monitorConfig);

// Determine output format
$format = $_GET['format'] ?? 'json';

// Set appropriate headers
if ($format === 'prometheus') {
    header('Content-Type: text/plain; version=0.0.4');
    echo $monitor->exportMetrics('prometheus');
} else {
    header('Content-Type: application/json');
    $health = $monitor->getHealthStatus();
    
    // Set HTTP status code based on health
    $statusCode = 200; // healthy
    if ($health['status'] === 'degraded') {
        $statusCode = 200; // still operational
    } elseif ($health['status'] === 'critical') {
        $statusCode = 503; // service unavailable
    }
    
    http_response_code($statusCode);
    echo json_encode($health, JSON_PRETTY_PRINT);
}
