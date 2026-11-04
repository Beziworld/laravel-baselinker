<?php

declare(strict_types=1);

use Beziworld\BaseLinker\Exceptions\ApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('posts a form with the method, JSON parameters and the token header', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'orders' => []])]);

    $response = baseLinkerClient()->call('getOrders', ['date_confirmed_from' => 1700000000, 'filter_email' => 'jan@example.pl']);

    expect($response)->toBe(['orders' => []]);

    Http::assertSent(static fn(Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api.baselinker.com/connector.php'
        && $request->isForm()
        && $request->header('X-BLToken') === [TEST_TOKEN]
        && ($request->data()['method'] ?? null) === 'getOrders'
        && sentParameters($request) === ['date_confirmed_from' => 1700000000, 'filter_email' => 'jan@example.pl']);
});

it('sends an empty JSON object when there are no parameters', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS'])]);

    baseLinkerClient()->call('getInventories');

    Http::assertSent(static fn(Request $request): bool => ($request->data()['parameters'] ?? null) === '{}');
});

it('keeps unicode and plus signs of base64 payloads intact in the form body', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS'])]);

    baseLinkerClient()->call('addOrderInvoiceFile', ['file' => 'data:ab+c/d==', 'external_invoice_number' => 'FV/1/Łódź']);

    Http::assertSent(static function (Request $request): bool {
        return str_contains($request->body(), 'ab%2Bc')
            && sentParameters($request) === ['file' => 'data:ab+c/d==', 'external_invoice_number' => 'FV/1/Łódź'];
    });
});

it('raises the error envelope as an ApiException with code and message', function (): void {
    Http::fake(['*' => Http::response(['status' => 'ERROR', 'error_code' => 'ERROR_BAD_TOKEN', 'error_message' => 'Invalid user token'], 200, ['Content-Type' => 'text/html; charset=UTF-8'])]);

    try {
        baseLinkerClient()->call('getOrders');
        $this->fail('Expected an ApiException.');
    } catch (ApiException $exception) {
        expect($exception->errorCode())->toBe('ERROR_BAD_TOKEN')
            ->and($exception->errorMessage())->toBe('Invalid user token')
            ->and($exception->apiMethod())->toBe('getOrders')
            ->and($exception->isBadToken())->toBeTrue()
            ->and($exception->getMessage())->toContain('ERROR_BAD_TOKEN');
    }
});

it('raises an ApiException for a response that is not a JSON envelope', function (): void {
    Http::fake(['*' => Http::response('<html>maintenance</html>', 200)]);

    baseLinkerClient()->call('getOrders');
})->throws(ApiException::class, 'not a JSON envelope');

it('raises an ApiException for HTTP errors', function (): void {
    Http::fake(['*' => Http::response('Bad gateway', 502)]);

    try {
        baseLinkerClient()->call('getOrders');
        $this->fail('Expected an ApiException.');
    } catch (ApiException $exception) {
        expect($exception->httpStatus())->toBe(502)->and($exception->errorCode())->toBeNull();
    }
});

it('wraps connection failures', function (): void {
    Http::fake(static fn() => throw new ConnectionException('timed out'));

    baseLinkerClient()->call('getOrders');
})->throws(ApiException::class, 'Could not connect to BaseLinker: timed out');

it('never retries a failed write', function (): void {
    Http::fake(['*' => Http::response(['status' => 'ERROR', 'error_code' => 'ERROR_UNKNOWN', 'error_message' => 'x'])]);

    expect(static fn() => baseLinkerClient()->orders()->addOrder(['order_status_id' => 1]))->toThrow(ApiException::class);

    Http::assertSentCount(1);
});
