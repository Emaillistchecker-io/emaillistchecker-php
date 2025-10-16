<?php

/**
 * Example: Batch email verification
 */

require_once __DIR__ . '/../vendor/autoload.php';

use EmailListChecker\EmailListChecker;
use EmailListChecker\Exceptions\EmailListCheckerException;

// Replace with your actual API key
$apiKey = 'your_api_key_here';

try {
    // Initialize client
    $client = new EmailListChecker($apiKey);

    // List of emails to verify
    $emails = [
        'user1@example.com',
        'user2@example.com',
        'user3@example.com',
        'invalid@invalid-domain-xyz.com',
        'test@gmail.com'
    ];

    echo "Submitting batch of " . count($emails) . " emails...\n";

    // Submit batch
    $batch = $client->verifyBatch($emails, 'My Test Batch');
    $batchId = $batch['id'];

    echo "Batch submitted successfully!\n";
    echo "Batch ID: {$batchId}\n";
    echo "Status: {$batch['status']}\n";
    echo "Total emails: {$batch['total_emails']}\n\n";

    // Monitor progress
    echo "Monitoring progress...\n";
    $previousProgress = 0;

    while (true) {
        $status = $client->getBatchStatus($batchId);

        if ($status['progress'] !== $previousProgress) {
            echo "Progress: {$status['progress']}% ";
            echo "({$status['processed_emails']}/{$status['total_emails']} processed)\n";
            $previousProgress = $status['progress'];
        }

        if ($status['status'] === 'completed') {
            echo "\nBatch verification completed!\n\n";
            break;
        } elseif ($status['status'] === 'failed') {
            echo "\nBatch verification failed!\n";
            exit(1);
        }

        sleep(2);  // Wait 2 seconds before checking again
    }

    // Get final statistics
    $finalStatus = $client->getBatchStatus($batchId);
    echo "=== Final Statistics ===\n";
    echo "Total: {$finalStatus['total_emails']}\n";
    echo "Valid: {$finalStatus['valid_emails']}\n";
    echo "Invalid: {$finalStatus['invalid_emails']}\n";
    echo "Unknown: {$finalStatus['unknown_emails']}\n\n";

    // Download results
    echo "Downloading results...\n";
    $results = $client->getBatchResults($batchId, 'json', 'all');

    echo "\n=== Results ===\n";
    foreach ($results['data'] as $result) {
        $status = match ($result['result']) {
            'deliverable' => '✓',
            'undeliverable' => '✗',
            'risky' => '⚠',
            default => '?'
        };
        echo "{$status} {$result['email']}: {$result['result']} ({$result['reason']})\n";
    }
} catch (EmailListCheckerException $e) {
    echo "Error: {$e->getMessage()}\n";
    if ($e->getStatusCode()) {
        echo "Status Code: {$e->getStatusCode()}\n";
    }
}
