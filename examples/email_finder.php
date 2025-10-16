<?php

/**
 * Example: Email Finder
 */

require_once __DIR__ . '/../vendor/autoload.php';

use EmailListChecker\EmailListChecker;
use EmailListChecker\Exceptions\EmailListCheckerException;

// Replace with your actual API key
$apiKey = 'your_api_key_here';

try {
    // Initialize client
    $client = new EmailListChecker($apiKey);

    // Example 1: Find email by name and domain
    echo "=== Find Email by Name ===\n";
    $result = $client->findEmail('John', 'Doe', 'example.com');

    echo "Email found: {$result['email']}\n";
    echo "Confidence: {$result['confidence']}%\n";
    echo "Pattern: {$result['pattern']}\n";
    echo "Verified: " . ($result['verified'] ? 'Yes' : 'No') . "\n";

    if (!empty($result['alternatives'])) {
        echo "\nAlternative patterns:\n";
        foreach ($result['alternatives'] as $alt) {
            echo "  - {$alt}\n";
        }
    }

    echo "\n";

    // Example 2: Find emails by domain
    echo "=== Find Emails by Domain ===\n";
    $domainResults = $client->findByDomain('example.com', 10);

    echo "Domain: {$domainResults['domain']}\n";
    echo "Total found: {$domainResults['total_found']}\n";

    if (!empty($domainResults['patterns'])) {
        echo "\nCommon email patterns:\n";
        foreach ($domainResults['patterns'] as $pattern) {
            echo "  - {$pattern}\n";
        }
    }

    echo "\nFound emails:\n";
    foreach ($domainResults['emails'] as $email) {
        echo "  - {$email['email']} (Last verified: {$email['last_verified']})\n";
    }

    echo "\n";

    // Example 3: Find emails by company
    echo "=== Find Emails by Company ===\n";
    $companyResults = $client->findByCompany('Acme Corporation', 10);

    echo "Company: {$companyResults['company']}\n";
    echo "Total found: {$companyResults['total_found']}\n";

    if (!empty($companyResults['possible_domains'])) {
        echo "\nPossible domains:\n";
        foreach ($companyResults['possible_domains'] as $domain) {
            echo "  - {$domain}\n";
        }
    }

    echo "\nFound emails:\n";
    foreach ($companyResults['emails'] as $email) {
        echo "  - {$email['email']} ({$email['domain']})\n";
    }
} catch (EmailListCheckerException $e) {
    echo "Error: {$e->getMessage()}\n";
    if ($e->getStatusCode()) {
        echo "Status Code: {$e->getStatusCode()}\n";
    }
}
