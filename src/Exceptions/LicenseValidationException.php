<?php

namespace Bellesoft\LicenseClient\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class LicenseValidationException extends HttpException
{
    protected array $responseBody;

    public function __construct(
        int $statusCode,
        string $message = 'License validation failed.',
        ?array $responseBody = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($statusCode, $message, $previous);
        $this->responseBody = $responseBody ?? [];
    }

    public function getResponseBody(): array
    {
        return $this->responseBody;
    }

    public static function fromResponse(int $statusCode, $responseBody): self
    {
        $message = 'License validation failed.';
        $body = \is_array($responseBody) ? $responseBody : [];

        if (!empty($body['message'])) {
            $message = \is_string($body['message']) ? $body['message'] : $message;
        } elseif (!empty($body['error'])) {
            $message = \is_string($body['error']) ? $body['error'] : $message;
        } elseif (!empty($body['errors']) && \is_string($body['errors'])) {
            $message = $body['errors'];
        }

        return new self($statusCode, $message, $body);
    }
}
