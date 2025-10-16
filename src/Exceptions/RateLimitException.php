<?php

namespace EmailListChecker\Exceptions;

/**
 * Exception thrown when API rate limit is exceeded
 */
class RateLimitException extends EmailListCheckerException
{
    /**
     * @var int Retry after seconds
     */
    protected $retryAfter;

    /**
     * Create a new rate limit exception
     *
     * @param string $message
     * @param int $retryAfter
     * @param int|null $statusCode
     * @param array|null $responseData
     */
    public function __construct(
        string $message,
        int $retryAfter = 60,
        ?int $statusCode = 429,
        ?array $responseData = null
    ) {
        parent::__construct($message, $statusCode, $responseData);
        $this->retryAfter = $retryAfter;
    }

    /**
     * Get retry after seconds
     *
     * @return int
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
