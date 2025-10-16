<?php

namespace EmailListChecker;

use EmailListChecker\Exceptions\ApiException;
use EmailListChecker\Exceptions\AuthenticationException;
use EmailListChecker\Exceptions\EmailListCheckerException;
use EmailListChecker\Exceptions\InsufficientCreditsException;
use EmailListChecker\Exceptions\RateLimitException;
use EmailListChecker\Exceptions\ValidationException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

/**
 * EmailListChecker API Client
 *
 * This class provides methods to interact with the EmailListChecker API.
 *
 * @package EmailListChecker
 */
class EmailListChecker
{
    /**
     * @var string API key
     */
    protected $apiKey;

    /**
     * @var string Base URL for API
     */
    protected $baseUrl;

    /**
     * @var int Request timeout in seconds
     */
    protected $timeout;

    /**
     * @var Client Guzzle HTTP client
     */
    protected $httpClient;

    /**
     * Create a new EmailListChecker instance
     *
     * @param string $apiKey Your EmailListChecker API key
     * @param string $baseUrl API base URL (default: https://platform.emaillistchecker.io/api/v1)
     * @param int $timeout Request timeout in seconds (default: 30)
     */
    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://platform.emaillistchecker.io/api/v1',
        int $timeout = 30
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'EmailListChecker-PHP/1.0.0',
            ],
        ]);
    }

    /**
     * Make HTTP request to API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $options Request options
     * @return array Response data
     * @throws EmailListCheckerException
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $endpoint = '/' . ltrim($endpoint, '/');
            $response = $this->httpClient->request($method, $endpoint, $options);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data ?? [];
        } catch (RequestException $e) {
            $statusCode = $e->getCode();
            $responseBody = null;

            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $responseBody = json_decode($e->getResponse()->getBody()->getContents(), true);
            }

            // Handle rate limiting
            if ($statusCode === 429) {
                $retryAfter = 60;
                if ($e->hasResponse() && $e->getResponse()->hasHeader('Retry-After')) {
                    $retryAfter = (int) $e->getResponse()->getHeader('Retry-After')[0];
                }

                throw new RateLimitException(
                    sprintf('Rate limit exceeded. Retry after %d seconds', $retryAfter),
                    $retryAfter,
                    $statusCode,
                    $responseBody
                );
            }

            // Handle authentication errors
            if ($statusCode === 401) {
                throw new AuthenticationException(
                    $responseBody['error'] ?? 'Invalid API key',
                    $statusCode,
                    $responseBody
                );
            }

            // Handle insufficient credits
            if ($statusCode === 402) {
                throw new InsufficientCreditsException(
                    $responseBody['error'] ?? 'Insufficient credits',
                    $statusCode,
                    $responseBody
                );
            }

            // Handle validation errors
            if ($statusCode === 422) {
                throw new ValidationException(
                    $responseBody['message'] ?? 'Validation error',
                    $statusCode,
                    $responseBody
                );
            }

            // Handle other API errors
            throw new ApiException(
                $responseBody['error'] ?? sprintf('API error: %d', $statusCode),
                $statusCode,
                $responseBody
            );
        } catch (GuzzleException $e) {
            throw new EmailListCheckerException('Request failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify a single email address
     *
     * @param string $email Email address to verify
     * @param int|null $timeout Verification timeout in seconds (5-60)
     * @param bool $smtpCheck Perform SMTP verification (default: true)
     * @return array Verification result
     * @throws EmailListCheckerException
     */
    public function verify(string $email, ?int $timeout = null, bool $smtpCheck = true): array
    {
        $params = [
            'email' => $email,
            'smtp_check' => $smtpCheck,
        ];

        if ($timeout !== null) {
            $params['timeout'] = $timeout;
        }

        $response = $this->request('POST', '/verify', [
            'json' => $params,
        ]);

        return $response['data'] ?? $response;
    }

    /**
     * Submit emails for batch verification
     *
     * @param array $emails List of email addresses (max 10,000)
     * @param string|null $name Name for this batch
     * @param string|null $callbackUrl Webhook URL for completion notification
     * @param bool $autoStart Start verification immediately (default: true)
     * @return array Batch submission result
     * @throws EmailListCheckerException
     */
    public function verifyBatch(
        array $emails,
        ?string $name = null,
        ?string $callbackUrl = null,
        bool $autoStart = true
    ): array {
        $data = [
            'emails' => $emails,
            'auto_start' => $autoStart,
        ];

        if ($name !== null) {
            $data['name'] = $name;
        }

        if ($callbackUrl !== null) {
            $data['callback_url'] = $callbackUrl;
        }

        $response = $this->request('POST', '/verify/batch', [
            'json' => $data,
        ]);

        return $response['data'] ?? $response;
    }

    /**
     * Get batch verification status
     *
     * @param int $batchId Batch ID
     * @return array Batch status
     * @throws EmailListCheckerException
     */
    public function getBatchStatus(int $batchId): array
    {
        $response = $this->request('GET', sprintf('/verify/batch/%d', $batchId));

        return $response['data'] ?? $response;
    }

    /**
     * Download batch verification results
     *
     * @param int $batchId Batch ID
     * @param string $format Output format - 'json', 'csv', 'txt' (default: 'json')
     * @param string $filter Filter results - 'all', 'valid', 'invalid', 'risky', 'unknown'
     * @return array|string Results in requested format
     * @throws EmailListCheckerException
     */
    public function getBatchResults(int $batchId, string $format = 'json', string $filter = 'all')
    {
        $response = $this->request('GET', sprintf('/verify/batch/%d/results', $batchId), [
            'query' => [
                'format' => $format,
                'filter' => $filter,
            ],
        ]);

        if ($format === 'json') {
            return $response['data'] ?? $response;
        }

        return $response;
    }

    /**
     * Find email address by name and domain
     *
     * @param string $firstName First name
     * @param string $lastName Last name
     * @param string $domain Domain (e.g., 'example.com')
     * @return array Found email information
     * @throws EmailListCheckerException
     */
    public function findEmail(string $firstName, string $lastName, string $domain): array
    {
        $response = $this->request('POST', '/finder/email', [
            'json' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'domain' => $domain,
            ],
        ]);

        return $response['data'] ?? $response;
    }

    /**
     * Find emails by domain
     *
     * @param string $domain Domain to search
     * @param int $limit Results per request (1-100, default: 10)
     * @param int $offset Pagination offset (default: 0)
     * @return array Found emails
     * @throws EmailListCheckerException
     */
    public function findByDomain(string $domain, int $limit = 10, int $offset = 0): array
    {
        $response = $this->request('POST', '/finder/domain', [
            'json' => [
                'domain' => $domain,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);

        return $response['data'] ?? $response;
    }

    /**
     * Find emails by company name
     *
     * @param string $company Company name
     * @param int $limit Results limit (1-100, default: 10)
     * @return array Found emails
     * @throws EmailListCheckerException
     */
    public function findByCompany(string $company, int $limit = 10): array
    {
        $response = $this->request('POST', '/finder/company', [
            'json' => [
                'company' => $company,
                'limit' => $limit,
            ],
        ]);

        return $response['data'] ?? $response;
    }

    /**
     * Get current credit balance
     *
     * @return array Credit information
     * @throws EmailListCheckerException
     */
    public function getCredits(): array
    {
        $response = $this->request('GET', '/credits');

        return $response['data'] ?? $response;
    }

    /**
     * Get API usage statistics
     *
     * @return array Usage statistics
     * @throws EmailListCheckerException
     */
    public function getUsage(): array
    {
        $response = $this->request('GET', '/usage');

        return $response['data'] ?? $response;
    }

    /**
     * Get all verification lists
     *
     * @return array List of verification batches
     * @throws EmailListCheckerException
     */
    public function getLists(): array
    {
        $response = $this->request('GET', '/lists');

        return $response['data'] ?? $response;
    }

    /**
     * Delete a verification list
     *
     * @param int $listId List ID to delete
     * @return array Deletion confirmation
     * @throws EmailListCheckerException
     */
    public function deleteList(int $listId): array
    {
        return $this->request('DELETE', sprintf('/lists/%d', $listId));
    }
}
