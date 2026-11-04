<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Enums;

/**
 * Shipment tracking status codes returned by getOrderPackages and getCourierPackagesStatusHistory.
 */
enum TrackingStatus: int
{
    case Unknown = 0;
    case LabelCreated = 1;
    case Shipped = 2;
    case NotDelivered = 3;
    case OutForDelivery = 4;
    case Delivered = 5;
    case Returned = 6;
    case Aviso = 7;
    case WaitingAtPoint = 8;
    case Lost = 9;
    case Canceled = 10;
    case OnTheWay = 11;

    /**
     * Whether no further status changes are expected.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Returned, self::Lost, self::Canceled], true);
    }
}
