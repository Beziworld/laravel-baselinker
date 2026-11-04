<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

/**
 * Base Connect integrations, contractors and trade credit.
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class Connect extends Resource
{
    /**
     * Retrieves a list of contractors connected to the selected Base Connect integration.
     *
     * @param int $connectIntegrationId Connect integration ID
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getConnectIntegrationContractors
     */
    public function getConnectIntegrationContractors(int $connectIntegrationId): array
    {
        $response = $this->client->call('getConnectIntegrationContractors', ['connect_integration_id' => $connectIntegrationId]);

        return $this->items($response, 'contractors');
    }

    /**
     * Retrieves a list of all Base Connect integrations on this account.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getConnectIntegrations
     */
    public function getConnectIntegrations(): array
    {
        $response = $this->client->call('getConnectIntegrations');

        return $this->items($response, 'integrations');
    }

    /**
     * Adds a manual credit settlement (repayment) for a chosen contractor in Base Connect.
     *
     * @param int $connectContractorId Contractor ID
     * @param float $amount Settlement amount
     * @param array<string, mixed> $options Optional fields: message, order_id.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=addConnectContractorCreditSettlement
     */
    public function addConnectContractorCreditSettlement(int $connectContractorId, float $amount, array $options = []): array
    {
        return $this->client->call('addConnectContractorCreditSettlement', ['connect_contractor_id' => $connectContractorId, 'amount' => $amount] + $options);
    }

    /**
     * Retrieves the trade credit history of a Base Connect contractor.
     *
     * @param int $connectContractorId Contractor ID
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getConnectContractorCreditHistory
     */
    public function getConnectContractorCreditHistory(int $connectContractorId): array
    {
        $response = $this->client->call('getConnectContractorCreditHistory', ['connect_contractor_id' => $connectContractorId]);

        return $this->items($response, 'credit_data');
    }

    /**
     * Sets new trade credit limit for chosen contractor.
     *
     * @param int $connectContractorId Contractor ID
     * @param float $newLimit New limit value
     * @param array<string, mixed> $options Optional fields: message.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=setConnectContractorCreditLimit
     */
    public function setConnectContractorCreditLimit(int $connectContractorId, float $newLimit, array $options = []): array
    {
        return $this->client->call('setConnectContractorCreditLimit', ['connect_contractor_id' => $connectContractorId, 'new_limit' => $newLimit] + $options);
    }
}
