<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Enums;

/**
 * Order journal event types returned by getJournalList (field "log_type").
 */
enum JournalEventType: int
{
    case OrderCreated = 1;
    case OrderConfirmationDownloaded = 2;
    case Payment = 3;
    case OrderOrInvoiceOrReceiptRemoved = 4;
    case OrdersMerged = 5;
    case OrderSplit = 6;
    case InvoiceIssued = 7;
    case ReceiptIssued = 8;
    case PackageCreated = 9;
    case PackageDeleted = 10;
    case DeliveryDataEdited = 11;
    case ProductAdded = 12;
    case ProductEdited = 13;
    case ProductRemoved = 14;
    case BuyerBlacklisted = 15;
    case OrderDataEdited = 16;
    case OrderCopied = 17;
    case StatusChanged = 18;
    case InvoiceDeleted = 19;
    case ReceiptDeleted = 20;
    case InvoiceDataEdited = 21;
    case PackageStatusChanged = 22;

    /**
     * Meaning of "object_id" for this event type, or null when the API sends no object.
     */
    public function objectMeaning(): ?string
    {
        return match ($this) {
            self::OrdersMerged => 'merged order id',
            self::OrderSplit => 'new order id',
            self::InvoiceIssued => 'invoice id',
            self::PackageCreated => 'package id',
            self::PackageDeleted => 'deleted package id',
            self::ProductRemoved => 'deleted order product id',
            self::OrderCopied => 'created order id',
            self::StatusChanged => 'order status id',
            self::PackageStatusChanged => 'package id',
            default => null,
        };
    }
}
