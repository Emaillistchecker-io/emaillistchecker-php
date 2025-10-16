<?php

/**
 * Example: Verify a single email address
 */

require_once __DIR__ . '/../vendor/autoload.php';

use EmailListChecker\EmailListChecker;
use EmailListChecker\Exceptions\EmailListCheckerException;

// Replace with your actual API key
$apiKey = 'your_api_key_here';

try {
    // Initialize client
    $client = new EmailListChecker($apiKey);

    // Verify an email
    echo "Verifying email...\n";
    $result = $client->verify('test@example.com');

    // Display results
    echo "\n=== Verification Result ===\n";
    echo "Email: {$result['email']}\n";
    echo "Result: {$result['result']}\n";
    echo "Reason: {$result['reason']}\n";
    echo "Score: {$result['score']}\n";
    echo "\n=== Email Details ===\n";
    echo "Disposable: " . ($result['disposable'] ? 'Yes' : 'No') . "\n";
    echo "Role-based: " . ($result['role'] ? 'Yes' : 'No') . "\n";
    echo "Free provider: " . ($result['free'] ? 'Yes' : 'No') . "\n";
    echo "SMTP Provider: {$result['smtp_provider']}\n";
    echo "Domain: {$result['domain']}\n";

    if (!empty($result['mx_records'])) {
        echo "\nMX Records:\n";
        foreach ($result['mx_records'] as $mx) {
            echo "  - {$mx}\n";
        }
    }
} catch (EmailListCheckerException $e) {
    echo "Error: {$e->getMessage()}\n";
    if ($e->getStatusCode()) {
        echo "Status Code: {$e->getStatusCode()}\n";
    }
}
