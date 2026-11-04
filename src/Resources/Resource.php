<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

use Beziworld\BaseLinker\BaseLinkerClient;
use Beziworld\BaseLinker\Data\BatchResult;
use Beziworld\BaseLinker\Support\Cast;
use Beziworld\BaseLinker\Support\FileEncoder;

/**
 * Base class of the API resource groups.
 *
 * Resources only shape parameters and responses; every request goes through
 * BaseLinkerClient::call(), which handles authentication, rate limiting and
 * the error envelope.
 */
abstract class Resource
{
    public function __construct(protected readonly BaseLinkerClient $client) {}

    /**
     * Integer field of a response, or null when it is absent.
     *
     * @param array<array-key, mixed> $response
     */
    protected function int(array $response, string $key): ?int
    {
        return Cast::int($response[$key] ?? null);
    }

    /**
     * List of integers stored under a response key.
     *
     * @param array<array-key, mixed> $response
     * @return list<int>
     */
    protected function ints(array $response, string $key): array
    {
        $ids = [];

        foreach (Cast::arr($response[$key] ?? null) as $id) {
            $id = Cast::int($id);

            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * List of records stored under a response key; non-array entries are skipped.
     *
     * @param array<array-key, mixed> $response
     * @return list<array<string, mixed>>
     */
    protected function items(array $response, string $key): array
    {
        return Cast::records($response[$key] ?? null);
    }

    /**
     * Array stored under a response key, keys preserved.
     *
     * @param array<array-key, mixed> $response
     * @return array<array-key, mixed>
     */
    protected function map(array $response, string $key): array
    {
        return Cast::arr($response[$key] ?? null);
    }

    /**
     * Records keyed by identifier (for example products keyed by product id), mapped with a factory.
     *
     * The identifier key is injected as "id" when the record does not carry one.
     *
     * @template T
     *
     * @param array<array-key, mixed> $response
     * @param callable(array<string, mixed>): T $factory
     * @return array<int, T>
     */
    protected function keyed(array $response, string $key, callable $factory): array
    {
        $mapped = [];

        foreach (Cast::arr($response[$key] ?? null) as $id => $row) {
            if (is_array($row)) {
                /** @var array<string, mixed> $row */
                $mapped[(int) $id] = $factory($row + ['id' => (int) $id]);
            }
        }

        return $mapped;
    }

    /**
     * Removes parameters left at null, so optional identifiers are not sent.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    protected function withoutNulls(array $parameters): array
    {
        return array_filter($parameters, static fn(mixed $item): bool => $item !== null);
    }

    /**
     * Sends chunks one by one and sums their counters and warnings.
     *
     * Chunks are sent sequentially and never retried; when a chunk fails,
     * the exception reaches the caller and later chunks are not sent.
     *
     * @param list<array<array-key, mixed>> $chunks
     * @param callable(array<array-key, mixed>): BatchResult $send
     */
    protected function batched(array $chunks, callable $send): BatchResult
    {
        $result = BatchResult::empty();

        foreach ($chunks as $chunk) {
            $result = $result->merge($send($chunk));
        }

        return $result;
    }

    /**
     * Encodes binary file contents in the "data:" + base64 form expected by file upload methods.
     */
    protected function encodeFile(string $contents): string
    {
        return FileEncoder::encode($contents);
    }

    /**
     * Encodes a receipt file: a JWS e-receipt token is sent as is, any other file as "data:" + base64.
     */
    protected function encodeReceiptFile(string $contents): string
    {
        return FileEncoder::isJws($contents) ? $contents : FileEncoder::encode($contents);
    }
}
