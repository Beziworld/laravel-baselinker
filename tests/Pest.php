<?php

declare(strict_types=1);

use Beziworld\BaseLinker\BaseLinkerClient;
use Beziworld\BaseLinker\BaseLinkerManager;
use Beziworld\BaseLinker\Tests\TestCase;
use Illuminate\Http\Client\Request;

const TEST_TOKEN = '1234-abcd-test-token';

uses(TestCase::class)->in('Unit', 'Feature');

/**
 * Client of the default test connection.
 */
function baseLinkerClient(?string $connection = null): BaseLinkerClient
{
    return app(BaseLinkerManager::class)->connection($connection);
}

/**
 * Decoded "parameters" JSON of a recorded request.
 *
 * @return array<array-key, mixed>
 */
function sentParameters(Request $request): array
{
    $decoded = json_decode((string) ($request->data()['parameters'] ?? ''), true);

    return is_array($decoded) ? $decoded : [];
}
