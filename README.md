# EmailListChecker PHP SDK

[![PHP Version](https://img.shields.io/badge/php-%5E7.4%20%7C%7C%20%5E8.0-blue.svg)](https://www.php.net/downloads/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Official PHP SDK for the [EmailListChecker](https://emaillistchecker.io) email verification API.

## Features

- **Email Verification** - Verify single or bulk email addresses
- **Email Finder** - Discover email addresses by name, domain, or company
- **Credit Management** - Check balance and usage
- **Batch Processing** - Async verification of large lists
- **Type Hints** - Full type hinting for better IDE support
- **PSR-4 Autoloading** - Follows PHP-FIG standards
- **Exception Handling** - Comprehensive exception classes

## Requirements

- PHP 7.4 or higher
- Composer
- ext-json

## Installation

Install via Composer using git:

```bash
composer require emaillistchecker/emaillistchecker-php:dev-main
```

Or add to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Emaillistchecker-io/emaillistchecker-php"
        }
    ],
    "require": {
        "emaillistchecker/emaillistchecker-php": "dev-main"
    }
}
```

Then run:

```bash
composer install
```

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use EmailListChecker\EmailListChecker;

// Initialize client
$client = new EmailListChecker('your_api_key_here');

// Verify an email
$result = $client->verify('test@example.com');
echo "Result: " . $result['result'] . "\n";  // deliverable, undeliverable, risky, unknown
echo "Score: " . $result['score'] . "\n";     // 0.0 to 1.0
```

## Get Your API Key

1. Sign up at [platform.emaillistchecker.io](https://platform.emaillistchecker.io/register)
2. Get your API key from the [API Dashboard](https://platform.emaillistchecker.io/api)
3. Start verifying!

## Usage Examples

### Single Email Verification

```php
<?php

use EmailListChecker\EmailListChecker;

$client = new EmailListChecker('your_api_key');

// Verify single email
$result = $client->verify('user@example.com');

if ($result['result'] === 'deliverable') {
    echo "✓ Email is valid and deliverable\n";
} elseif ($result['result'] === 'undeliverable') {
    echo "✗ Email is invalid\n";
} elseif ($result['result'] === 'risky') {
    echo "⚠ Email is risky (catch-all, disposable, etc.)\n";
} else {
    echo "? Unable to determine\n";
}

// Check details
echo "Disposable: " . ($result['disposable'] ? 'Yes' : 'No') . "\n";
echo "Role account: " . ($result['role'] ? 'Yes' : 'No') . "\n";
echo "Free provider: " . ($result['free'] ? 'Yes' : 'No') . "\n";
echo "SMTP provider: " . $result['smtp_provider'] . "\n";
```

### Batch Email Verification

```php
<?php

use EmailListChecker\EmailListChecker;

$client = new EmailListChecker('your_api_key');

// Submit batch for verification
$emails = [
    'user1@example.com',
    'user2@example.com',
    'user3@example.com'
];

$batch = $client->verifyBatch($emails, 'My Campaign List');
$batchId = $batch['id'];

echo "Batch ID: {$batchId}\n";
echo "Status: {$batch['status']}\n";

// Check progress
while (true) {
    $status = $client->getBatchStatus($batchId);
    echo "Progress: {$status['progress']}%\n";

    if ($status['status'] === 'completed') {
        break;
    }

    sleep(5);  // Wait 5 seconds before checking again
}

// Download results
$results = $client->getBatchResults($batchId, 'json', 'all');

foreach ($results['data'] as $emailData) {
    echo "{$emailData['email']}: {$emailData['result']}\n";
}
```

### Email Finder

```php
<?php

use EmailListChecker\EmailListChecker;

$client = new EmailListChecker('your_api_key');

// Find email by name and domain
$result = $client->findEmail('John', 'Doe', 'example.com');

echo "Found: {$result['email']}\n";
echo "Confidence: {$result['confidence']}%\n";
echo "Verified: " . ($result['verified'] ? 'Yes' : 'No') . "\n";

// Find all emails for a domain
$domainResults = $client->findByDomain('example.com', 50);

foreach ($domainResults['emails'] as $email) {
    echo "{$email['email']} - Last verified: {$email['last_verified']}\n";
}

// Find emails by company name
$companyResults = $client->findByCompany('Acme Corporation');

echo "Possible domains: " . implode(', ', $companyResults['possible_domains']) . "\n";
foreach ($companyResults['emails'] as $email) {
    echo "{$email['email']} ({$email['domain']})\n";
}
```

### Credit Management

```php
<?php

use EmailListChecker\EmailListChecker;

$client = new EmailListChecker('your_api_key');

// Check credit balance
$credits = $client->getCredits();
echo "Available credits: {$credits['balance']}\n";
echo "Used this month: {$credits['used_this_month']}\n";
echo "Current plan: {$credits['plan']}\n";

// Get usage statistics
$usage = $client->getUsage();
echo "Total API calls: {$usage['total_requests']}\n";
echo "Successful: {$usage['successful_requests']}\n";
echo "Failed: {$usage['failed_requests']}\n";
```

### List Management

```php
<?php

use EmailListChecker\EmailListChecker;

$client = new EmailListChecker('your_api_key');

// Get all lists
$lists = $client->getLists();

foreach ($lists as $list) {
    echo "ID: {$list['id']}\n";
    echo "Name: {$list['name']}\n";
    echo "Status: {$list['status']}\n";
    echo "Total emails: {$list['total_emails']}\n";
    echo "Valid: {$list['valid_emails']}\n";
    echo "---\n";
}

// Delete a list
$client->deleteList(123);
```

## Error Handling

```php
<?php

use EmailListChecker\EmailListChecker;
use EmailListChecker\Exceptions\AuthenticationException;
use EmailListChecker\Exceptions\InsufficientCreditsException;
use EmailListChecker\Exceptions\RateLimitException;
use EmailListChecker\Exceptions\ValidationException;
use EmailListChecker\Exceptions\EmailListCheckerException;

$client = new EmailListChecker('your_api_key');

try {
    $result = $client->verify('test@example.com');
} catch (AuthenticationException $e) {
    echo "Invalid API key\n";
} catch (InsufficientCreditsException $e) {
    echo "Not enough credits\n";
} catch (RateLimitException $e) {
    echo "Rate limit exceeded. Retry after {$e->getRetryAfter()} seconds\n";
} catch (ValidationException $e) {
    echo "Validation error: {$e->getMessage()}\n";
} catch (EmailListCheckerException $e) {
    echo "API error: {$e->getMessage()}\n";
    if ($e->getStatusCode()) {
        echo "Status code: {$e->getStatusCode()}\n";
    }
}
```

## API Response Format

### Verification Result

```php
[
    'email' => 'user@example.com',
    'result' => 'deliverable',  // deliverable | undeliverable | risky | unknown
    'reason' => 'VALID',         // VALID | INVALID | ACCEPT_ALL | DISPOSABLE | etc.
    'disposable' => false,       // Is temporary/disposable email
    'role' => false,             // Is role-based (info@, support@, etc.)
    'free' => false,             // Is free provider (gmail, yahoo, etc.)
    'score' => 1.0,              // Deliverability score (0.0 - 1.0)
    'smtp_provider' => 'google', // Email provider
    'mx_records' => ['mx1.google.com', 'mx2.google.com'],
    'domain' => 'example.com',
    'spam_trap' => false,
    'mx_found' => true
]
```

## Configuration

### Custom Timeout

```php
<?php

use EmailListChecker\EmailListChecker;

// Set custom timeout (default: 30 seconds)
$client = new EmailListChecker(
    'your_api_key',
    'https://platform.emaillistchecker.io/api/v1',
    60  // 60 seconds timeout
);
```

### Custom Base URL

```php
<?php

use EmailListChecker\EmailListChecker;

// Use custom API endpoint (for testing or private instances)
$client = new EmailListChecker(
    'your_api_key',
    'https://custom-api.example.com/api/v1'
);
```

## Development

### Running Tests

```bash
composer test
```

### Code Style

```bash
composer cs-fix
```

## Support

- **Documentation**: [platform.emaillistchecker.io/api](https://platform.emaillistchecker.io/api)
- **Email**: support@emaillistchecker.io
- **Issues**: [GitHub Issues](https://github.com/Emaillistchecker-io/emaillistchecker-php/issues)

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

Made with ❤️ by [EmailListChecker](https://emaillistchecker.io)
