<?php

declare(strict_types=1);

use Beziworld\BaseLinker\Exceptions\InvalidResponse;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('decodes a label from plain base64', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'extension' => 'zpl', 'label' => base64_encode('^XA^FDlabel^FS^XZ')])]);

    $label = baseLinkerClient()->couriers()->getLabel('inpost', packageNumber: '620100045678901234');

    expect($label->contents)->toBe('^XA^FDlabel^FS^XZ')
        ->and($label->extension)->toBe('zpl')
        ->and($label->mimeType())->toBe('text/plain')
        ->and($label->filename('label-1'))->toBe('label-1.zpl');

    Http::assertSent(static fn(Request $request): bool => sentParameters($request) === ['courier_code' => 'inpost', 'package_number' => '620100045678901234']);
});

it('decodes an invoice file with the data: prefix and keeps the number', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'invoice' => 'data:' . base64_encode('%PDF-1.7 invoice'), 'invoice_number' => 'FV 101/03/2023'])]);

    $file = baseLinkerClient()->invoices()->getInvoiceFile(77, ['get_external' => true]);

    expect($file->contents)->toBe('%PDF-1.7 invoice')
        ->and($file->number)->toBe('FV 101/03/2023')
        ->and($file->extension)->toBe('pdf')
        ->and($file->mimeType())->toBe('application/pdf')
        ->and($file->filename())->toBe('FV_101_03_2023.pdf')
        ->and($file->raw)->toBe(['invoice_number' => 'FV 101/03/2023']);
});

it('accepts a full data URI', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'file' => 'data:application/pdf;base64,' . base64_encode('%PDF doc'), 'document_number' => 'PZ 1/2026'])]);

    $file = baseLinkerClient()->documents()->getInventoryDocumentFile(5);

    expect($file->contents)->toBe('%PDF doc')->and($file->number)->toBe('PZ 1/2026');
});

it('takes the extension from the returned file name', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'file' => 'data:' . base64_encode('<xml/>'), 'cost_invoice_no' => 'FZ/1', 'file_name' => 'invoice.XML'])]);

    $file = baseLinkerClient()->purchaseOrders()->getInventoryPurchaseOrderFile(8);

    expect($file->name)->toBe('invoice.XML')->and($file->extension)->toBe('xml')->and($file->filename())->toBe('invoice.XML');
});

it('raises InvalidResponse when the file is missing', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'extension' => 'pdf'])]);

    baseLinkerClient()->couriers()->getProtocol('dpd', ['package_ids' => [1]]);
})->throws(InvalidResponse::class, 'no file in the "protocol" field');

it('raises InvalidResponse for malformed base64', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'label' => '***not base64***'])]);

    baseLinkerClient()->couriers()->getLabel('dpd', 5);
})->throws(InvalidResponse::class, 'not valid base64');

it('decodes every fulfillment delivery label', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'labels' => [
        ['type' => 'box', 'position' => 1, 'box_position' => 1, 'extension' => 'pdf', 'label' => base64_encode('L1')],
        ['type' => 'box', 'position' => 2, 'box_position' => 2, 'extension' => 'pdf', 'label' => base64_encode('L2')],
    ]])]);

    $labels = baseLinkerClient()->fulfillment()->getInventoryFulfillmentDeliveryLabels(3, 'box');

    expect($labels)->toHaveCount(2)->and($labels[1]->contents)->toBe('L2')->and($labels[1]->raw['position'])->toBe(2);
});

it('encodes uploaded files as data: plus base64', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS'])]);

    baseLinkerClient()->invoices()->addOrderInvoiceFile(9, "%PDF\xFF\xFE binary", 'FV/9');

    Http::assertSent(static fn(Request $request): bool => sentParameters($request) === [
        'invoice_id' => 9,
        'file' => 'data:' . base64_encode("%PDF\xFF\xFE binary"),
        'external_invoice_number' => 'FV/9',
    ]);
});

it('sends a JWS e-receipt token without encoding', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS'])]);
    $jws = 'eyJhbGciOiJSUzI1NiJ9.eyJyZWNlaXB0IjoxfQ.c2lnbmF0dXJl';

    baseLinkerClient()->receipts()->addOrderReceiptFile(4, $jws, 'PAR/4');

    Http::assertSent(static fn(Request $request): bool => (sentParameters($request)['file'] ?? null) === $jws);
});
