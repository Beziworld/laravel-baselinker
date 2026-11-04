<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;

/**
 * Order source: its type (personal, shop, allegro, amazon, ...) and id, as used by getOrders filters.
 */
final readonly class OrderSource
{
    public function __construct(
        public string $type,
        public int $id,
        public string $name,
    ) {}

    /**
     * Flattens the "sources" map of getOrderSources ({type: {id: name}}).
     *
     * @param array<array-key, mixed> $sources
     * @return list<self>
     */
    public static function listFromSources(array $sources): array
    {
        $list = [];

        foreach ($sources as $type => $entries) {
            foreach (Cast::arr($entries) as $id => $name) {
                $list[] = new self((string) $type, (int) $id, Cast::string($name) ?? '');
            }
        }

        return $list;
    }
}
