<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Enums;

/**
 * Order return journal event types returned by getOrderReturnJournalList (field "log_type").
 */
enum ReturnJournalEventType: int
{
    case ReturnCreated = 1;
    case ReturnAccepted = 2;
    case ReturnCompleted = 3;
    case ReturnCanceled = 4;
    case ReturnRefunded = 5;
    case DeliveryDataEdited = 6;
    case ProductAdded = 7;
    case ProductEdited = 8;
    case ProductRemoved = 9;
    case ReturnDataEdited = 10;
    case StatusChanged = 11;
    case ItemStatusChanged = 12;
}
