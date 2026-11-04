<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

/**
 * Listing Agents (automatic listing of products to marketplaces).
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class ListingAgents extends Resource
{
    /**
     * Returns listing queue rows created by a Listing Agent with their status and errors, for example offers rejected by the marketplace.
     *
     * @param int $listingAgentId Listing Agent identifier
     * @param array<string, mixed> $filters Filters: filter_status_group, filter_product_id, page.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=getListingAgentQueueItems
     */
    public function getListingAgentQueueItems(int $listingAgentId, array $filters = []): array
    {
        return $this->client->call('getListingAgentQueueItems', ['listing_agent_id' => $listingAgentId] + $filters);
    }

    /**
     * Returns per-product results of one Listing Agent run: products added to the listing queue (with the current queue status) and products rejected before the queue with their errors.
     *
     * @param int $listingAgentRunId Run identifier (getListingAgentRunsList)
     * @param array<string, mixed> $filters Filters: filter_type, page.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=getListingAgentRunItems
     */
    public function getListingAgentRunItems(int $listingAgentRunId, array $filters = []): array
    {
        return $this->client->call('getListingAgentRunItems', ['listing_agent_run_id' => $listingAgentRunId] + $filters);
    }

    /**
     * Returns Listing Agent runs from the last 14 days, newest first, including runs that failed to start.
     *
     * @param array<string, mixed> $filters Filters: listing_agent_id, filter_marketplace_code, date_from, date_to, filter_problems_only, page.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=getListingAgentRunsList
     */
    public function getListingAgentRunsList(array $filters = []): array
    {
        return $this->client->call('getListingAgentRunsList', $filters);
    }

    /**
     * Returns the history of a Listing Agent from the last 14 days: creation, settings changes, enabling, disabling and deletion, with changed fields.
     *
     * @param int $listingAgentId Listing Agent identifier
     * @param array<string, mixed> $options Optional fields: page.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=getListingAgentSettingsHistory
     */
    public function getListingAgentSettingsHistory(int $listingAgentId, array $options = []): array
    {
        return $this->client->call('getListingAgentSettingsHistory', ['listing_agent_id' => $listingAgentId] + $options);
    }

    /**
     * Returns details of up to 10 Listing Agents: settings, effective marketplace account settings, detected issues, latest runs and queue summary.
     *
     * @param array<array-key, mixed> $listingAgentIds API field "listing_agent_ids". Listing Agent IDs (getListingAgentsList), up to 10 per request
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getListingAgentsData
     */
    public function getListingAgentsData(array $listingAgentIds): array
    {
        $response = $this->client->call('getListingAgentsData', ['listing_agent_ids' => $listingAgentIds]);

        return $this->items($response, 'listing_agents');
    }

    /**
     * Returns Listing Agents (automatic listing of products to marketplaces) configured in the account, with their current status and the reason of the last stop.
     *
     * @param array<string, mixed> $filters Filters: filter_marketplace_code, filter_account_id, filter_is_active, page.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=getListingAgentsList
     */
    public function getListingAgentsList(array $filters = []): array
    {
        return $this->client->call('getListingAgentsList', $filters);
    }
}
