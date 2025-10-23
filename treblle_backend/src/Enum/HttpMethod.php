<?php

namespace App\Enum;

enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case CONNECT = 'CONNECT';
    case TRACE = 'TRACE';

    /**
     * Get all HTTP method values as an array
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn(self $method) => $method->value, self::cases());
    }

    /**
     * Check if a given string is a valid HTTP method
     *
     * @param string $method
     * @return bool
     */
    public static function isValid(string $method): bool
    {
        return self::tryFrom(strtoupper($method)) !== null;
    }

    /**
     * Try to create an instance from a string (case-insensitive)
     *
     * @param string $method
     * @return self|null
     */
    public static function tryFromCaseInsensitive(string $value): ?self
    {
        return self::tryFrom(strtoupper($value));
    }
}

