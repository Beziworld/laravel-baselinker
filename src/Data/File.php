<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Exceptions\InvalidResponse;
use Beziworld\BaseLinker\Support\Cast;
use Beziworld\BaseLinker\Support\FileEncoder;

/**
 * Downloaded file (label, protocol, invoice, warehouse document) as a binary string.
 */
final readonly class File
{
    /**
     * @param array<string, mixed> $raw Response fields other than the file payload.
     */
    public function __construct(
        public string $contents,
        public ?string $extension,
        public ?string $number = null,
        public ?string $name = null,
        public array $raw = [],
    ) {}

    /**
     * Decodes the base64 payload stored under a response key.
     *
     * @param array<array-key, mixed> $response
     *
     * @throws InvalidResponse When the payload is missing or not valid base64.
     */
    public static function fromResponse(array $response, string $key, ?string $numberKey = null, ?string $nameKey = null): self
    {
        $payload = Cast::text($response[$key] ?? null) ?? throw InvalidResponse::missingFile($key);
        unset($response[$key]);
        $name = $nameKey !== null ? Cast::text($response[$nameKey] ?? null) : null;
        $nameExtension = $name !== null ? strtolower(pathinfo($name, PATHINFO_EXTENSION)) : '';

        /** @var array<string, mixed> $response */
        return new self(
            contents: FileEncoder::decode($payload, $key),
            extension: Cast::text($response['extension'] ?? null) ?? ($nameExtension !== '' ? $nameExtension : 'pdf'),
            number: $numberKey !== null ? Cast::text($response[$numberKey] ?? null) : null,
            name: $name,
            raw: $response,
        );
    }

    /**
     * MIME type derived from the extension.
     */
    public function mimeType(): string
    {
        return match (strtolower((string) $this->extension)) {
            'pdf' => 'application/pdf',
            'html' => 'text/html',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'xml' => 'application/xml',
            'zpl', 'epl', 'dpl' => 'text/plain',
            default => 'application/octet-stream',
        };
    }

    /**
     * Suggested file name: the name returned by the API, or the number (or a fallback) with the extension.
     */
    public function filename(string $fallback = 'document'): string
    {
        if ($this->name !== null) {
            return $this->name;
        }

        $base = preg_replace('/[^A-Za-z0-9._-]+/', '_', $this->number ?? $fallback) ?? $fallback;

        return $base . '.' . ($this->extension ?? 'pdf');
    }
}
