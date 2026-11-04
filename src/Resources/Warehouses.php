<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

use Beziworld\BaseLinker\Data\Warehouse;

/**
 * Warehouses and their locations, location types, zones, racks and maps.
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class Warehouses extends Resource
{
    /**
     * Adds a new warehouse available in Base inventories.
     *
     * @param array<string, mixed> $warehouse Fields: warehouse_id, name, description, stock_edition, country, address, postcode, city.
     * @see https://api.baselinker.com/index.php?method=addInventoryWarehouse
     */
    public function addInventoryWarehouse(array $warehouse): ?int
    {
        $response = $this->client->call('addInventoryWarehouse', $warehouse);

        return $this->int($response, 'warehouse_id');
    }

    /**
     * Removes the warehouse available in Base inventories.
     *
     * @param int $warehouseId ID of the warehouse
     * @see https://api.baselinker.com/index.php?method=deleteInventoryWarehouse
     */
    public function deleteInventoryWarehouse(int $warehouseId): void
    {
        $this->client->call('deleteInventoryWarehouse', ['warehouse_id' => $warehouseId]);
    }

    /**
     * Returns detailed information about a specific warehouse map, including the full JSON representation of the visual map layout.
     *
     * @param int $mapId Warehouse map identifier
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=getInventoryMapDetails
     */
    public function getInventoryMapDetails(int $mapId): array
    {
        return $this->client->call('getInventoryMapDetails', ['map_id' => $mapId]);
    }

    /**
     * Retrieves a list of warehouse maps available in the Base inventory system.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryMaps
     */
    public function getInventoryMaps(): array
    {
        $response = $this->client->call('getInventoryMaps');

        return $this->items($response, 'maps');
    }

    /**
     * Retrieves a list of warehouses available in Base inventories.
     *
     * @return list<Warehouse>
     * @see https://api.baselinker.com/index.php?method=getInventoryWarehouses
     */
    public function getInventoryWarehouses(): array
    {
        $response = $this->client->call('getInventoryWarehouses');

        return array_map(Warehouse::fromArray(...), $this->items($response, 'warehouses'));
    }

    /**
     * Adds or updates a warehouse location in a Base inventory warehouse.
     *
     * @param array<string, mixed> $location Fields: location_id, warehouse_id, warehouse_type, name, color, is_pickable, priority, location_type_id, zone_id, rack_id.
     * @see https://api.baselinker.com/index.php?method=addInventoryWarehouseLocation
     */
    public function addInventoryWarehouseLocation(array $location): ?int
    {
        $response = $this->client->call('addInventoryWarehouseLocation', $location);

        return $this->int($response, 'location_id');
    }

    /**
     * Adds or updates a warehouse location type.
     *
     * @param array<string, mixed> $locationType Fields: location_type_id, name, max_quantity, max_weight, width, height, depth, is_transfer_bin, is_receiving_bin.
     * @see https://api.baselinker.com/index.php?method=addInventoryWarehouseLocationType
     */
    public function addInventoryWarehouseLocationType(array $locationType): ?int
    {
        $response = $this->client->call('addInventoryWarehouseLocationType', $locationType);

        return $this->int($response, 'location_type_id');
    }

    /**
     * Deletes a single warehouse location from a Base inventory warehouse.
     *
     * @param int $warehouseId Warehouse identifier returned by getInventoryWarehouses (the warehouse_id field)
     * @param string $warehouseType Warehouse type returned by getInventoryWarehouses (the warehouse_type field)
     * @param int $locationId ID of the location to delete (the value of location_id returned by getInventoryWarehouseLocations )
     * @see https://api.baselinker.com/index.php?method=deleteInventoryWarehouseLocation
     */
    public function deleteInventoryWarehouseLocation(int $warehouseId, string $warehouseType, int $locationId): void
    {
        $this->client->call('deleteInventoryWarehouseLocation', ['warehouse_id' => $warehouseId, 'warehouse_type' => $warehouseType, 'location_id' => $locationId]);
    }

    /**
     * Deletes a single warehouse location type.
     *
     * @param int $locationTypeId ID of the location type to delete (the value of location_type_id returned by getInventoryWarehouseLocationTypes )
     * @see https://api.baselinker.com/index.php?method=deleteInventoryWarehouseLocationType
     */
    public function deleteInventoryWarehouseLocationType(int $locationTypeId): void
    {
        $this->client->call('deleteInventoryWarehouseLocationType', ['location_type_id' => $locationTypeId]);
    }

    /**
     * Retrieves the list of warehouse location types.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryWarehouseLocationTypes
     */
    public function getInventoryWarehouseLocationTypes(): array
    {
        $response = $this->client->call('getInventoryWarehouseLocationTypes');

        return $this->items($response, 'location_types');
    }

    /**
     * Retrieves the list of warehouse locations (slots/bins) defined for a given Base inventory warehouse.
     *
     * @param int $warehouseId Warehouse identifier returned by getInventoryWarehouses (the warehouse_id field)
     * @param string $warehouseType Warehouse type returned by getInventoryWarehouses (the warehouse_type field)
     * @param array<string, mixed> $filters Filters: page, filter_location_id, filter_name, filter_rack_id, filter_zone_id.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryWarehouseLocations
     */
    public function getInventoryWarehouseLocations(int $warehouseId, string $warehouseType, array $filters = []): array
    {
        $response = $this->client->call('getInventoryWarehouseLocations', ['warehouse_id' => $warehouseId, 'warehouse_type' => $warehouseType] + $filters);

        return $this->items($response, 'locations');
    }

    /**
     * Adds or updates a warehouse zone.
     *
     * @param array<string, mixed> $zone Fields: zone_id, name, color, location_prefix.
     * @see https://api.baselinker.com/index.php?method=addInventoryWarehouseZone
     */
    public function addInventoryWarehouseZone(array $zone): ?int
    {
        $response = $this->client->call('addInventoryWarehouseZone', $zone);

        return $this->int($response, 'zone_id');
    }

    /**
     * Deletes a warehouse zone.
     *
     * @param int $zoneId ID of the zone to delete (the value of zone_id returned by getInventoryWarehouseZones )
     * @see https://api.baselinker.com/index.php?method=deleteInventoryWarehouseZone
     */
    public function deleteInventoryWarehouseZone(int $zoneId): void
    {
        $this->client->call('deleteInventoryWarehouseZone', ['zone_id' => $zoneId]);
    }

    /**
     * Retrieves the list of warehouse zones.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryWarehouseZones
     */
    public function getInventoryWarehouseZones(): array
    {
        $response = $this->client->call('getInventoryWarehouseZones');

        return $this->items($response, 'zones');
    }

    /**
     * Adds or updates a warehouse rack.
     *
     * @param array<string, mixed> $rack Fields: rack_id, name, zone_id, layout, location_prefix.
     * @see https://api.baselinker.com/index.php?method=addInventoryWarehouseRack
     */
    public function addInventoryWarehouseRack(array $rack): ?int
    {
        $response = $this->client->call('addInventoryWarehouseRack', $rack);

        return $this->int($response, 'rack_id');
    }

    /**
     * Deletes a warehouse rack.
     *
     * @param int $rackId ID of the rack to delete (the value of rack_id returned by getInventoryWarehouseRacks )
     * @see https://api.baselinker.com/index.php?method=deleteInventoryWarehouseRack
     */
    public function deleteInventoryWarehouseRack(int $rackId): void
    {
        $this->client->call('deleteInventoryWarehouseRack', ['rack_id' => $rackId]);
    }

    /**
     * Retrieves the list of warehouse racks.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryWarehouseRacks
     */
    public function getInventoryWarehouseRacks(): array
    {
        $response = $this->client->call('getInventoryWarehouseRacks');

        return $this->items($response, 'racks');
    }
}
