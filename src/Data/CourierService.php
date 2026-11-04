<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;

/**
 * Additional courier service returned by getCourierServices.
 */
final readonly class CourierService
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}

    /**
     * Maps the "services" object ({id: name}).
     *
     * @param array<array-key, mixed> $services
     * @return list<self>
     */
    public static function listFromMap(array $services): array
    {
        $list = [];

        foreach ($services as $id => $name) {
            $list[] = new self((string) $id, Cast::string($name) ?? '');
        }

        return $list;
    }
}
