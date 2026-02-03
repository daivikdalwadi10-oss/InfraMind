<?php

declare(strict_types=1);

namespace InfraMind\Core;

/**
 * HTTP Response builder.
 */
class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private array $data = [];
    private bool $sent = false;

    public function __construct(int $statusCode = 200)
    {
        $this->statusCode = $statusCode;
        $this->setHeader('Content-Type', 'application/json');
    }

    /**
     * Set HTTP status code.
     */
    public function setStatus(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Set response header.
     */
    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Set response data.
     */
    public function setData(mixed $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Success response.
     */
    public function success(mixed $data = null, ?string $message = null): self
    {
        $this->statusCode = 200;
        $this->data = [
            'success' => true,
            'data' => $data,
        ];
        if ($message !== null) {
            $this->data['message'] = $message;
        }
        return $this;
    }

    /**
     * Error response.
     */
    public function error(string $message, int $statusCode = 400, array $errors = []): self
    {
        $this->statusCode = $statusCode;
        $this->data = [
            'success' => false,
            'message' => $message,
        ];
        if (!empty($errors)) {
            $this->data['errors'] = $errors;
        }
        return $this;
    }

    /**
     * Send response to client.
     */
    public function send(): void
    {
        if ($this->sent) {
            return;
        }

        http_response_code($this->statusCode);

        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        echo json_encode($this->data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $this->sent = true;
    }

    /**
     * Get response as array.
     */
    public function toArray(): array
    {
        return [
            'status' => $this->statusCode,
            'headers' => $this->headers,
            'data' => $this->data,
        ];
    }
}
