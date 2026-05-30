<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Small helpers for parsing request fields for the Deal handlers.
 */
final class DealField
{
    /**
     * Trimmed non-empty string for the key, or null when missing/blank/non-string.
     *
     * @param array<string, mixed> $body
     */
    public static function optionalString(array $body, string $key): ?string
    {
        $value = $body[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /** Reads the {dealId} path parameter, or an empty string when absent. */
    public static function pathId(ServerRequestInterface $request, string $name = 'dealId'): string
    {
        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE);

        if (is_array($params) && isset($params[$name]) && is_string($params[$name])) {
            return $params[$name];
        }

        return '';
    }
}
