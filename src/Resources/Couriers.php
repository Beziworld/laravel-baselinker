<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

use Beziworld\BaseLinker\Data\CourierService;
use Beziworld\BaseLinker\Data\CreatedPackage;
use Beziworld\BaseLinker\Data\File;
use Beziworld\BaseLinker\Data\Package;

/**
 * Couriers and shipments: packages, labels, protocols, documents and parcel pickups.
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class Couriers extends Resource
{
    /**
     * Creates a shipment in the system of the selected courier.
     *
     * @param int $orderId Order identifier
     * @param string $courierCode Courier code
     * @param array<array-key, mixed> $fields API field "fields". List of form fields retrieved from the request getCourierFields For checkbox and multi_select fields with multiple selection, the information...
     * @param array<array-key, mixed> $packages API field "packages". Array of shipments list, weight of at least one shipment required
     * @param array<string, mixed> $options Optional fields: account_id.
     * @see https://api.baselinker.com/index.php?method=createPackage
     */
    public function createPackage(int $orderId, string $courierCode, array $fields = [], array $packages = [], array $options = []): CreatedPackage
    {
        $response = $this->client->call('createPackage', ['order_id' => $orderId, 'courier_code' => $courierCode, 'fields' => $fields, 'packages' => $packages] + $options);

        return CreatedPackage::fromArray($response);
    }

    /**
     * Enters the shipping number and the name of the courier to the order (function used only to add shipments created outside Base).
     *
     * @param int $orderId Order identifier
     * @param string $courierCode Courier code (courier code retrieved with getCouriersList or custom courier name)
     * @param string $packageNumber Shipping number (consignment number)
     * @param int $pickupDate Date of dispatch (unix time format)
     * @param array<string, mixed> $options Optional fields: return_shipment.
     * @see https://api.baselinker.com/index.php?method=createPackageManual
     */
    public function createPackageManual(
        int $orderId,
        string $courierCode,
        string $packageNumber,
        int $pickupDate,
        array $options = [],
    ): CreatedPackage {
        $response = $this->client->call('createPackageManual', ['order_id' => $orderId, 'courier_code' => $courierCode, 'package_number' => $packageNumber, 'pickup_date' => $pickupDate] + $options);

        return CreatedPackage::fromArray($response);
    }

    /**
     * Deletes a previously created shipment.
     *
     * @param string $courierCode Courier code
     * @param ?int $packageId Shipment ID, optional if package_number is provided
     * @param ?string $packageNumber Shipping number (consignment number), optional if package_id was provided
     * @param array<string, mixed> $options Optional fields: force_delete.
     * @see https://api.baselinker.com/index.php?method=deleteCourierPackage
     */
    public function deleteCourierPackage(string $courierCode, ?int $packageId = null, ?string $packageNumber = null, array $options = []): void
    {
        $this->client->call('deleteCourierPackage', $this->withoutNulls(['courier_code' => $courierCode, 'package_id' => $packageId, 'package_number' => $packageNumber] + $options));
    }

    /**
     * Retrieves the history of the status list of the given shipments.
     *
     * @param array<array-key, mixed> $packageIds API field "package_ids". An array with a list of parcel IDs
     * @return array<array-key, mixed>
     * @see https://api.baselinker.com/index.php?method=getCourierPackagesStatusHistory
     */
    public function getCourierPackagesStatusHistory(array $packageIds): array
    {
        $response = $this->client->call('getCourierPackagesStatusHistory', ['package_ids' => $packageIds]);

        return $this->map($response, 'packages_history');
    }

    /**
     * Downloads shipments previously created for the selected order.
     *
     * @param int $orderId Order identifier
     * @return list<Package>
     * @see https://api.baselinker.com/index.php?method=getOrderPackages
     */
    public function getOrderPackages(int $orderId): array
    {
        $response = $this->client->call('getOrderPackages', ['order_id' => $orderId]);

        return array_map(Package::fromArray(...), $this->items($response, 'packages'));
    }

    /**
     * Gets detailed information about a package.
     *
     * @param int $packageId Shipment ID
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=getPackageDetails
     */
    public function getPackageDetails(int $packageId): array
    {
        return $this->client->call('getPackageDetails', ['package_id' => $packageId]);
    }

    /**
     * Returns available shipment quotations for a shipment handled by the selected courier.
     *
     * @param int $orderId Order identifier
     * @param string $courierCode Courier code
     * @param array<array-key, mixed> $fields API field "fields". List of form fields retrieved from the request getCourierFields Each element must contain the field ID and a value compatible with its returned type
     * @param array<array-key, mixed> $packages API field "packages". Array of shipments
     * @param array<string, mixed> $options Optional fields: account_id.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=getPricePackage
     */
    public function getPricePackage(int $orderId, string $courierCode, array $fields = [], array $packages = [], array $options = []): array
    {
        return $this->client->call('getPricePackage', ['order_id' => $orderId, 'courier_code' => $courierCode, 'fields' => $fields, 'packages' => $packages] + $options);
    }

    /**
     * Retrieves a list of accounts connected with a courier.
     *
     * @param string $courierCode Courier code
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getCourierAccounts
     */
    public function getCourierAccounts(string $courierCode): array
    {
        $response = $this->client->call('getCourierAccounts', ['courier_code' => $courierCode]);

        return $this->items($response, 'accounts');
    }

    /**
     * Retrieves the form fields for creating shipments for the selected courier.
     *
     * @param string $courierCode Courier code
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=getCourierFields
     */
    public function getCourierFields(string $courierCode): array
    {
        return $this->client->call('getCourierFields', ['courier_code' => $courierCode]);
    }

    /**
     * Retrieves additional courier services, which depend on other shipment settings.
     *
     * @param string $courierCode Courier code
     * @param int $orderId Order identifier
     * @param array<array-key, mixed> $fields API field "fields". Form fields (the same as in createPackage method)
     * @param array<array-key, mixed> $packages API field "packages". Package information (the same as in createPackage method)
     * @param array<string, mixed> $options Optional fields: account_id.
     * @return list<CourierService>
     * @see https://api.baselinker.com/index.php?method=getCourierServices
     */
    public function getCourierServices(string $courierCode, int $orderId, array $fields = [], array $packages = [], array $options = []): array
    {
        $response = $this->client->call('getCourierServices', ['courier_code' => $courierCode, 'order_id' => $orderId, 'fields' => $fields, 'packages' => $packages] + $options);

        return CourierService::listFromMap($this->map($response, 'services'));
    }

    /**
     * Retrieves a list of available couriers.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getCouriersList
     */
    public function getCouriersList(): array
    {
        $response = $this->client->call('getCouriersList');

        return $this->items($response, 'couriers');
    }

    /**
     * Downloads a parcel document.
     *
     * @param string $courierCode Courier code
     * @param string $documentType Types: manifest protocol label customs_document dangerous_goods_declaration
     * @param array<string, mixed> $options Optional fields: account_id, package_ids, package_numbers.
     * @see https://api.baselinker.com/index.php?method=getCourierDocument
     */
    public function getCourierDocument(string $courierCode, string $documentType, array $options = []): File
    {
        $response = $this->client->call('getCourierDocument', ['courier_code' => $courierCode, 'document_type' => $documentType] + $options);

        return File::fromResponse($response, 'document');
    }

    /**
     * Downloads a shipping label (consignment) for a selected shipment.
     *
     * @param string $courierCode Courier code
     * @param ?int $packageId Shipment ID, optional if package_number was provided
     * @param ?string $packageNumber Shipping number (consignment number), optional if package_id was provided
     * @see https://api.baselinker.com/index.php?method=getLabel
     */
    public function getLabel(string $courierCode, ?int $packageId = null, ?string $packageNumber = null): File
    {
        $response = $this->client->call('getLabel', $this->withoutNulls(['courier_code' => $courierCode, 'package_id' => $packageId, 'package_number' => $packageNumber]));

        return File::fromResponse($response, 'label');
    }

    /**
     * Downloads a parcel protocol for selected shipments if the protocol is available for chosen courier.
     *
     * @param string $courierCode Courier code
     * @param array<string, mixed> $options Optional fields: package_ids, package_numbers, account_id.
     * @see https://api.baselinker.com/index.php?method=getProtocol
     */
    public function getProtocol(string $courierCode, array $options = []): File
    {
        $response = $this->client->call('getProtocol', ['courier_code' => $courierCode] + $options);

        return File::fromResponse($response, 'protocol');
    }

    /**
     * Retrieves additional fields for a parcel pickup request.
     *
     * @param string $courierCode Courier code
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getRequestParcelPickupFields
     */
    public function getRequestParcelPickupFields(string $courierCode): array
    {
        $response = $this->client->call('getRequestParcelPickupFields', ['courier_code' => $courierCode]);

        return $this->items($response, 'fields');
    }

    /**
     * Requests a parcel pickup for previously created shipments.
     *
     * @param string $courierCode Courier code
     * @param array<string, mixed> $options Optional fields: package_ids, package_numbers, account_id, fields.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=runRequestParcelPickup
     */
    public function runRequestParcelPickup(string $courierCode, array $options = []): array
    {
        return $this->client->call('runRequestParcelPickup', ['courier_code' => $courierCode] + $options);
    }
}
