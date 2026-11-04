<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Facades;

use Beziworld\BaseLinker\BaseLinkerManager;
use Illuminate\Support\Facades\Facade;

/**
 * Facade of the connection manager; client methods go to the default connection.
 *
 * @method static \Beziworld\BaseLinker\BaseLinkerClient connection(?string $name = null)
 * @method static \Beziworld\BaseLinker\BaseLinkerClient using(string $token, ?string $name = null, array<string, mixed> $options = [])
 * @method static list<string> connectionNames()
 * @method static string getDefaultConnection()
 * @method static void purge(?string $name = null)
 * @method static array<string, mixed> call(string $method, array<array-key, mixed> $parameters = [])
 * @method static string name()
 * @method static \Beziworld\BaseLinker\BaseLinkerConfig config()
 * @method static \Beziworld\BaseLinker\Resources\Orders orders()
 * @method static \Beziworld\BaseLinker\Resources\PickPack pickPack()
 * @method static \Beziworld\BaseLinker\Resources\Invoices invoices()
 * @method static \Beziworld\BaseLinker\Resources\Receipts receipts()
 * @method static \Beziworld\BaseLinker\Resources\OrderReturns returns()
 * @method static \Beziworld\BaseLinker\Resources\Couriers couriers()
 * @method static \Beziworld\BaseLinker\Resources\Crm crm()
 * @method static \Beziworld\BaseLinker\Resources\Catalog inventory()
 * @method static \Beziworld\BaseLinker\Resources\Warehouses warehouses()
 * @method static \Beziworld\BaseLinker\Resources\Documents documents()
 * @method static \Beziworld\BaseLinker\Resources\PurchaseOrders purchaseOrders()
 * @method static \Beziworld\BaseLinker\Resources\Transfers transfers()
 * @method static \Beziworld\BaseLinker\Resources\Stocktakes stocktakes()
 * @method static \Beziworld\BaseLinker\Resources\Fulfillment fulfillment()
 * @method static \Beziworld\BaseLinker\Resources\Connect connect()
 * @method static \Beziworld\BaseLinker\Resources\ExternalStorages externalStorages()
 * @method static \Beziworld\BaseLinker\Resources\ListingAgents listingAgents()
 *
 * @see BaseLinkerManager
 * @see \Beziworld\BaseLinker\BaseLinkerClient
 */
final class BaseLinker extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BaseLinkerManager::class;
    }
}
