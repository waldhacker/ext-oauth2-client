<?php

namespace TYPO3\JsonResponse;

class GlobalStates
{
    private static array $headers = [];

    /**
     * @param mixed $value
     */
    public static function addHeader(string $key, $value, bool $replace = true): void
    {
        $value = trim($value);
        if ($replace) {
            static::$headers[$key] = [$value];
        } else {
            static::$headers[$key][] = $value;
        }
    }

    public static function getHeaders(): array
    {
        return static::$headers;
    }
}
