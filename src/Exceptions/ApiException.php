<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Exceptions;

use Throwable;

/**
 * Raised when BaseLinker rejects a request or cannot be reached.
 *
 * The API answers every call with HTTP 200; a failure is reported in the
 * envelope as status "ERROR" with "error_code" (for example
 * ERROR_BAD_TOKEN) and "error_message", exposed by errorCode() and
 * errorMessage(). Transport failures carry no error code.
 */
final class ApiException extends BaseLinkerException
{
    public function __construct(
        string $message,
        private readonly string $apiMethod,
        private readonly ?string $errorCode = null,
        private readonly ?string $errorMessage = null,
        private readonly ?int $httpStatus = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function fromEnvelope(string $method, ?string $code, ?string $message): self
    {
        $code = $code !== null && $code !== '' ? $code : 'UNKNOWN_ERROR';
        $message = $message !== null && $message !== '' ? $message : 'unknown error';

        return new self(sprintf('BaseLinker %s failed [%s]: %s', $method, $code, $message), $method, $code, $message);
    }

    public static function http(string $method, int $status): self
    {
        return new self(sprintf('BaseLinker %s failed with HTTP status %d.', $method, $status), $method, httpStatus: $status);
    }

    public static function malformed(string $method, int $status): self
    {
        return new self(sprintf('BaseLinker %s returned a response that is not a JSON envelope (HTTP %d).', $method, $status), $method, httpStatus: $status);
    }

    public static function transport(string $method, Throwable $previous): self
    {
        return new self('Could not connect to BaseLinker: ' . $previous->getMessage(), $method, previous: $previous);
    }

    /**
     * API method that failed, for example "getOrders".
     */
    public function apiMethod(): string
    {
        return $this->apiMethod;
    }

    /**
     * Value of "error_code" from the envelope, for example "ERROR_BAD_TOKEN"; null for transport errors.
     */
    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Value of "error_message" from the envelope; null for transport errors.
     */
    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function httpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function isBadToken(): bool
    {
        return $this->errorCode === 'ERROR_BAD_TOKEN';
    }
}
