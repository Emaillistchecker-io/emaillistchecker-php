<?php

namespace EmailListChecker\Exceptions;

use Exception;

/**
 * Base exception for EmailListChecker SDK
 */
class EmailListCheckerException extends Exception
{
    /**
     * @var int|null HTTP status code
     */
    protected $statusCode;

    /**
     * @var array|null API response data
     */
    protected $responseData;

    /**
     * Create a new exception instance
     *
     * @param string $message
     * @param int|null $statusCode
     * @param array|null $responseData
     * @param Exception|null $previous
     */
    public function __construct(
        string $message = "",
        ?int $statusCode = null,
        ?array $responseData = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
        $this->responseData = $responseData;
    }

    /**
     * Get the HTTP status code
     *
     * @return int|null
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Get the API response data
     *
     * @return array|null
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }
}
