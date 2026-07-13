<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use DateTimeImmutable;
use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Small helpers for parsing request fields for the Deal handlers.
 */
final class DealField
{
    /** Matches the `account_label` VARCHAR(255) column. */
    public const MAX_ACCOUNT_LABEL = 255;

    /**
     * `note` is a MySQL TEXT column (65 535 bytes). Cap by characters well below
     * that so even 4-byte utf8mb4 input can never overflow the column, and to
     * keep an unbounded free-text field from being abused for storage.
     */
    public const MAX_NOTE = 5000;

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

    /**
     * True when the value is a real calendar date in strict `YYYY-MM-DD` form.
     * Rejects impossible dates (e.g. `2026-13-45`) and any other shape, which
     * would otherwise reach the MySQL `date` column and raise a 500 instead of a
     * clean 422.
     */
    public static function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    /**
     * True when the value is a syntactically valid ULID. `owner_user_id`
     * references a user id (a ULID); validating the shape rejects malformed or
     * over-long input before it reaches the VARCHAR(26) column.
     */
    public static function isValidUlid(string $value): bool
    {
        return Ulid::isValid($value);
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
