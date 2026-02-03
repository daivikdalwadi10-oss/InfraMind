<?php

declare(strict_types=1);

namespace InfraMind\Core;

/**
 * HTTP Request wrapper for cleaner request handling.
 */
class Request
{
    private string $method;
    private string $path;
    private array $getParams;
    private array $postData;
    private array $headers;
    private ?string $body;
    private ?object $user = null;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = $this->normalizePath($_SERVER['REQUEST_URI'] ?? '/');
        $this->getParams = $_GET ?? [];
        $this->headers = $this->parseHeaders();
        $this->body = file_get_contents('php://input');
        $this->parseBody();
    }

    /**
     * Get HTTP method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get request path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get query parameter.
     */
    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->getParams[$key] ?? $default;
    }

    /**
     * Get all query parameters.
     */
    public function getQueryAll(): array
    {
        return $this->getParams;
    }

    /**
     * Get POST/JSON parameter.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->postData[$key] ?? $default;
    }

    /**
     * Get all POST/JSON data.
     */
    public function getAll(): array
    {
        return $this->postData;
    }

    /**
     * Get header value.
     */
    public function getHeader(string $key): ?string
    {
        $key = strtoupper(str_replace('-', '_', $key));
        return $this->headers[$key] ?? null;
    }

    /**
     * Get raw body.
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * Set authenticated user.
     */
    public function setUser(object $user): void
    {
        $this->user = $user;
    }

    /**
     * Get authenticated user.
     */
    public function getUser(): ?object
    {
        return $this->user;
    }

    /**
     * Check if request is JSON.
     */
    public function isJson(): bool
    {
        return strpos($this->getHeader('Content-Type') ?? '', 'application/json') !== false;
    }

    /**
     * Normalize path (remove query string, trailing slash).
     */
    private function normalizePath(string $path): string
    {
        // Remove query string
        $path = explode('?', $path)[0];
        // Remove trailing slash except for root
        $path = rtrim($path, '/');
        if (empty($path)) {
            $path = '/';
        }
        return $path;
    }

    /**
     * Parse request body based on content type.
     */
    private function parseBody(): void
    {
        if (empty($this->body)) {
            $this->postData = [];
            return;
        }

        if ($this->isJson()) {
            $decoded = json_decode($this->body, true);
            $this->postData = is_array($decoded) ? $decoded : [];
        } else {
            parse_str($this->body, $this->postData);
        }
    }

    /**
     * Parse HTTP headers.
     */
    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerKey = substr($key, 5);
                $headers[$headerKey] = $value;
            }
        }
        return $headers;
    }
}
