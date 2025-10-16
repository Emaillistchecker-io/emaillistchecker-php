<?php

/**
 * Example: Check credits and usage
 */

require_once __DIR__ . '/../vendor/autoload.php';

use EmailListChecker\EmailListChecker;
use EmailListChecker\Exceptions\EmailListCheckerException;

// Replace with your actual API key
$apiKey = 'your_api_key_here';

try {
    // Initialize client
    $client = new EmailListChecker($apiKey);

    // Get credit balance
    echo "=== Credit Balance ===\n";
    $credits = $client->getCredits();

    echo "Available credits: {$credits['balance']}\n";
    echo "Used this month: {$credits['used_this_month']}\n";
    echo "Current plan: {$credits['plan']}\n\n";

    // Get usage statistics
    echo "=== Usage Statistics ===\n";
    $usage = $client->getUsage();

    echo "Total API requests: {$usage['total_requests']}\n";
    echo "Successful requests: {$usage['successful_requests']}\n";
    echo "Failed requests: {$usage['failed_requests']}\n";

    // Calculate success rate
    if ($usage['total_requests'] > 0) {
        $successRate = ($usage['successful_requests'] / $usage['total_requests']) * 100;
        echo "Success rate: " . number_format($successRate, 2) . "%\n";
    }
} catch (EmailListCheckerException $e) {
    echo "Error: {$e->getMessage()}\n";
    if ($e->getStatusCode()) {
        echo "Status Code: {$e->getStatusCode()}\n";
    }
}
