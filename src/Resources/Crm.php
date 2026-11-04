<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

/**
 * CRM clients, client statuses and status groups.
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class Crm extends Resource
{
    /**
     * Adds a new CRM client.
     *
     * @param array<string, mixed> $client Fields: crm_client_id, status_id, star, contractor_id, login, phone, email, notes, invoice_company, invoice_fullname, invoice_address, invoice_postcode, invoice_city, invoice_state, invoice_country_code, invoice_tax_id, delivery_company, delivery_fullname, delivery_address, delivery_postcode, delivery_city, delivery_state, delivery_country_code, custom_extra_fields.
     * @see https://api.baselinker.com/index.php?method=addCrmClient
     */
    public function addCrmClient(array $client): ?int
    {
        $response = $this->client->call('addCrmClient', $client);

        return $this->int($response, 'crm_client_id');
    }

    /**
     * Removes a CRM client.
     *
     * @param int $crmClientId ID of the CRM client to delete
     * @see https://api.baselinker.com/index.php?method=deleteCrmClient
     */
    public function deleteCrmClient(int $crmClientId): void
    {
        $this->client->call('deleteCrmClient', ['crm_client_id' => $crmClientId]);
    }

    /**
     * Retrieves detailed data of a specific CRM client, including notes.
     *
     * @param int $crmClientId ID of the CRM client to retrieve
     * @param array<string, mixed> $options Optional fields: include_custom_extra_fields.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=getCrmClientData
     */
    public function getCrmClientData(int $crmClientId, array $options = []): array
    {
        return $this->client->call('getCrmClientData', ['crm_client_id' => $crmClientId] + $options);
    }

    /**
     * Returns extra fields defined for CRM clients.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getCrmClientExtraFields
     */
    public function getCrmClientExtraFields(): array
    {
        $response = $this->client->call('getCrmClientExtraFields');

        return $this->items($response, 'extra_fields');
    }

    /**
     * Retrieves a list of CRM clients.
     *
     * @param array<string, mixed> $filters Filters: page, filter_crm_client_id, filter_email, filter_phone, filter_login, filter_invoice_company, filter_invoice_tax_id, filter_invoice_fullname, filter_status_id.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getCrmClients
     */
    public function getCrmClients(array $filters = []): array
    {
        $response = $this->client->call('getCrmClients', $filters);

        return $this->items($response, 'clients');
    }

    /**
     * Creates a new CRM client status or updates an existing one if status_id is provided.
     *
     * @param array<string, mixed> $status Fields: status_id, name, color, group_id.
     * @see https://api.baselinker.com/index.php?method=addCrmClientStatus
     */
    public function addCrmClientStatus(array $status): ?int
    {
        $response = $this->client->call('addCrmClientStatus', $status);

        return $this->int($response, 'status_id');
    }

    /**
     * Creates a new CRM client status group or updates an existing one if group_id is provided.
     *
     * @param array<string, mixed> $group Fields: group_id, name.
     * @see https://api.baselinker.com/index.php?method=addCrmClientStatusGroup
     */
    public function addCrmClientStatusGroup(array $group): ?int
    {
        $response = $this->client->call('addCrmClientStatusGroup', $group);

        return $this->int($response, 'group_id');
    }

    /**
     * Deletes a CRM client status.
     *
     * @param int $statusId ID of the status to delete
     * @param int $targetStatusId ID of the status to which items with the deleted status will be migrated
     * @see https://api.baselinker.com/index.php?method=deleteCrmClientStatus
     */
    public function deleteCrmClientStatus(int $statusId, int $targetStatusId): void
    {
        $this->client->call('deleteCrmClientStatus', ['status_id' => $statusId, 'target_status_id' => $targetStatusId]);
    }

    /**
     * Deletes a CRM client status group.
     *
     * @param int $groupId ID of the status group to delete
     * @see https://api.baselinker.com/index.php?method=deleteCrmClientStatusGroup
     */
    public function deleteCrmClientStatusGroup(int $groupId): void
    {
        $this->client->call('deleteCrmClientStatusGroup', ['group_id' => $groupId]);
    }

    /**
     * Returns the list of CRM client status groups.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getCrmClientStatusGroups
     */
    public function getCrmClientStatusGroups(): array
    {
        $response = $this->client->call('getCrmClientStatusGroups');

        return $this->items($response, 'status_groups');
    }

    /**
     * Returns CRM client statuses created by the user in the order manager.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getCrmClientStatuses
     */
    public function getCrmClientStatuses(): array
    {
        $response = $this->client->call('getCrmClientStatuses');

        return $this->items($response, 'statuses');
    }
}
