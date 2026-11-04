<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Support;

use Beziworld\BaseLinker\Exceptions\InvalidResponse;

/**
 * Converts files between binary strings and the base64 forms used by the API.
 *
 * Uploads expect "data:" followed by plain base64 (without a MIME type).
 * Downloads return either plain base64 (labels, protocols) or the same
 * "data:" form (invoices, warehouse documents); a full data URI
 * ("data:application/pdf;base64,...") is accepted as well.
 *
 * The API documentation warns that "+" must be sent as "%2B": the HTTP client
 * form-encodes the whole body, so base64 payloads arrive intact without any
 * manual replacement.
 *
 * @internal
 */
final class FileEncoder
{
    public static function encode(string $contents): string
    {
        return 'data:' . base64_encode($contents);
    }

    /**
     * Decodes a base64 payload, with or without the "data:" prefix.
     *
     * @throws InvalidResponse When the payload is not valid base64.
     */
    public static function decode(string $payload, string $field = 'file'): string
    {
        $payload = trim($payload);

        if (str_starts_with($payload, 'data:')) {
            $comma = strpos($payload, ',');
            $payload = $comma !== false && str_contains(substr($payload, 0, $comma), ';base64')
                ? substr($payload, $comma + 1)
                : substr($payload, 5);
        }

        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);

        return $decoded !== false ? $decoded : throw InvalidResponse::malformedFile($field);
    }

    /**
     * Whether a string is a compact JWS token (three base64url segments), as used by e-receipts.
     */
    public static function isJws(string $contents): bool
    {
        return preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $contents) === 1;
    }
}
