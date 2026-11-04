<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker;

use Beziworld\BaseLinker\Exceptions\ApiException;
use Beziworld\BaseLinker\Exceptions\RateLimitExceeded;
use Beziworld\BaseLinker\Resources\Catalog;
use Beziworld\BaseLinker\Resources\Connect;
use Beziworld\BaseLinker\Resources\Couriers;
use Beziworld\BaseLinker\Resources\Crm;
use Beziworld\BaseLinker\Resources\Documents;
use Beziworld\BaseLinker\Resources\ExternalStorages;
use Beziworld\BaseLinker\Resources\Fulfillment;
use Beziworld\BaseLinker\Resources\Invoices;
use Beziworld\BaseLinker\Resources\ListingAgents;
use Beziworld\BaseLinker\Resources\OrderReturns;
use Beziworld\BaseLinker\Resources\Orders;
use Beziworld\BaseLinker\Resources\PickPack;
use Beziworld\BaseLinker\Resources\PurchaseOrders;
use Beziworld\BaseLinker\Resources\Receipts;
use Beziworld\BaseLinker\Resources\Resource;
use Beziworld\BaseLinker\Resources\Stocktakes;
use Beziworld\BaseLinker\Resources\Transfers;
use Beziworld\BaseLinker\Resources\Warehouses;
use Beziworld\BaseLinker\Support\Cast;
use Beziworld\BaseLinker\Support\TokenRateLimiter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;

/**
 * BaseLinker API client bound to one token (one connection).
 *
 * Every API method is a form POST to connector.php with the fields "method"
 * and "parameters" (JSON) and the X-BLToken header. The API answers with
 * HTTP 200 even for errors; status "ERROR" in the envelope is raised as
 * ApiException. Requests are never retried automatically, because most
 * write methods are not idempotent.
 */
final class BaseLinkerClient
{
    /** @var array<class-string<Resource>, Resource> */
    private array $resources = [];

    public function __construct(
        private readonly HttpFactory $http,
        private readonly BaseLinkerConfig $config,
        private readonly ?TokenRateLimiter $limiter = null,
        private readonly string $name = 'default',
    ) {}

    /**
     * Name of the connection; runtime clients created with using() get a name derived from the token.
     */
    public function name(): string
    {
        return $this->name;
    }

    public function config(): BaseLinkerConfig
    {
        return $this->config;
    }

    /**
     * Calls any API method, including methods added to the API after this package was released.
     *
     * Returns the response without the "status" field.
     *
     * @param array<array-key, mixed> $parameters
     * @return array<string, mixed>
     *
     * @throws ApiException When BaseLinker reports an error or cannot be reached.
     * @throws RateLimitExceeded When the client-side limiter refuses the request.
     */
    public function call(string $method, array $parameters = []): array
    {
        $this->limiter?->acquire($this->config);

        try {
            $response = $this->http->createPendingRequest()
                ->asForm()
                ->acceptJson()
                ->timeout($this->config->timeout)
                ->connectTimeout($this->config->connectTimeout)
                ->withUserAgent('beziworld/laravel-baselinker')
                ->withHeaders(['X-BLToken' => $this->config->token])
                ->post($this->config->url, [
                    'method' => $method,
                    'parameters' => json_encode(
                        $parameters === [] ? new \stdClass() : $parameters,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
                    ),
                ]);
        } catch (ConnectionException $exception) {
            throw ApiException::transport($method, $exception);
        }

        $body = $response->json();

        if (! is_array($body) || ! isset($body['status'])) {
            throw $response->successful() ? ApiException::malformed($method, $response->status()) : ApiException::http($method, $response->status());
        }

        if ($body['status'] !== 'SUCCESS') {
            throw ApiException::fromEnvelope($method, Cast::string($body['error_code'] ?? null), Cast::string($body['error_message'] ?? null));
        }

        unset($body['status']);

        /** @var array<string, mixed> $body */
        return $body;
    }

    public function orders(): Orders
    {
        return $this->resource(Orders::class);
    }

    public function pickPack(): PickPack
    {
        return $this->resource(PickPack::class);
    }

    public function invoices(): Invoices
    {
        return $this->resource(Invoices::class);
    }

    public function receipts(): Receipts
    {
        return $this->resource(Receipts::class);
    }

    public function returns(): OrderReturns
    {
        return $this->resource(OrderReturns::class);
    }

    public function couriers(): Couriers
    {
        return $this->resource(Couriers::class);
    }

    public function crm(): Crm
    {
        return $this->resource(Crm::class);
    }

    public function inventory(): Catalog
    {
        return $this->resource(Catalog::class);
    }

    public function warehouses(): Warehouses
    {
        return $this->resource(Warehouses::class);
    }

    public function documents(): Documents
    {
        return $this->resource(Documents::class);
    }

    public function purchaseOrders(): PurchaseOrders
    {
        return $this->resource(PurchaseOrders::class);
    }

    public function transfers(): Transfers
    {
        return $this->resource(Transfers::class);
    }

    public function stocktakes(): Stocktakes
    {
        return $this->resource(Stocktakes::class);
    }

    public function fulfillment(): Fulfillment
    {
        return $this->resource(Fulfillment::class);
    }

    public function connect(): Connect
    {
        return $this->resource(Connect::class);
    }

    public function externalStorages(): ExternalStorages
    {
        return $this->resource(ExternalStorages::class);
    }

    public function listingAgents(): ListingAgents
    {
        return $this->resource(ListingAgents::class);
    }

    /**
     * @template T of Resource
     *
     * @param class-string<T> $class
     * @return T
     */
    private function resource(string $class): Resource
    {
        $resource = $this->resources[$class] ??= new $class($this);

        /** @var T $resource */
        return $resource;
    }
}
