<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

/**
 * Opaque, offset-based pagination cursor. The encoding is intentionally private
 * to the API surface — clients must treat it as opaque and only echo it back.
 */
final class Cursor
{
    public static function encode(int $offset): string
    {
        return rtrim(strtr(base64_encode('o:' . $offset), '+/', '-_'), '=');
    }

    /** Returns the decoded non-negative offset, or 0 when the cursor is absent or malformed. */
    public static function decodeOffset(?string $cursor): int
    {
        if ($cursor === null || $cursor === '') {
            return 0;
        }

        $decoded = base64_decode(strtr($cursor, '-_', '+/'), true);

        if ($decoded === false || !str_starts_with($decoded, 'o:')) {
            return 0;
        }

        $offset = (int) substr($decoded, 2);

        return max(0, $offset);
    }
}
