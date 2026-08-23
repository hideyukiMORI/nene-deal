<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Handoff;

use NeneDeal\Handoff\HttpInvoiceClient;
use NeneDeal\Handoff\InvoiceHandoffException;
use NeneDeal\Tests\Support\RecordingHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * Pins the wire contract Deal sends to the NeNe Invoice admin API.
 *
 * 🔴 What this file can and cannot prove. It asserts that Deal sends what
 * Invoice's `origin/main` required **when measured on 2026-08-23**
 * (`LineItemRequest::parseLines`, `CreateQuoteHandler`,
 * `CreateQuoteUseCase::ALLOWED_TAX_RATES_BPS`). It cannot notice Invoice
 * changing its contract afterwards — no test in this repo can. The live
 * cross-check is QA `#168` A-3 (invoice-handoff cross-check, not yet run).
 * Treating a green run here as "the handoff works" is the same mistake `#212`
 * was made of.
 */
final class HttpInvoiceClientTest extends TestCase
{
    private function client(RecordingHttpClient $http): HttpInvoiceClient
    {
        return new HttpInvoiceClient($http, new Psr17Factory(), 'https://invoice.example/', 'tok');
    }

    public function testDraftQuoteBodyMatchesTheInvoiceContractExactly(): void
    {
        $http = new RecordingHttpClient();
        $this->client($http)->createDraftQuote(4821, 250_000, 'Acme Corp');

        $body = $http->decodedBody(0);

        // Exact key set, not just presence: an extra field is as much a contract
        // drift as a missing one, and `currency` was exactly that (#212) — sent
        // for months, never read by CreateQuoteHandler.
        self::assertSame(['client_id', 'line_items'], array_keys($body));
        self::assertSame(4821, $body['client_id']);

        self::assertIsArray($body['line_items']);
        self::assertCount(1, $body['line_items']);

        $line = $body['line_items'][0];
        self::assertSame(['description', 'quantity', 'unit_price_cents', 'tax_rate_bps'], array_keys($line));
        self::assertSame('Acme Corp', $line['description']);
        self::assertSame(1, $line['quantity']);
        self::assertSame(250_000, $line['unit_price_cents']);
    }

    /**
     * The three names from `#212`. Kept as an explicit negative assertion rather
     * than relying on the key-set check above: if someone reshapes the payload,
     * this says *which* mistake was reintroduced.
     */
    public function testRegressionTheThreeMismatchedNamesAreGone(): void
    {
        $http = new RecordingHttpClient();
        $this->client($http)->createDraftQuote(1, 1000, 'x');

        $body = $http->decodedBody(0);

        self::assertArrayNotHasKey('lines', $body, 'Invoice reads `line_items`, never `lines` (#212).');
        self::assertArrayNotHasKey('currency', $body, 'CreateQuoteHandler never reads `currency` (#212).');
        self::assertArrayNotHasKey('unit_amount_cents', $body['line_items'][0], 'Invoice reads `unit_price_cents` (#212).');
        self::assertArrayHasKey('tax_rate_bps', $body['line_items'][0], 'Invoice requires `tax_rate_bps` (#212).');
    }

    /**
     * `tax_rate_bps` is not "any integer". Invoice rejects everything except
     * 800 and 1000, so a plausible-looking 0 — or a percentage mistaken for
     * basis points (10 instead of 1000) — is a 422, not a neutral default.
     */
    public function testTaxRateIsOneOfTheTwoValuesInvoiceAccepts(): void
    {
        $http = new RecordingHttpClient();
        $this->client($http)->createDraftQuote(1, 1000, 'x');

        self::assertContains(
            $http->decodedBody(0)['line_items'][0]['tax_rate_bps'],
            [800, 1000],
            'CreateQuoteUseCase::ALLOWED_TAX_RATES_BPS = [800, 1000] (measured 2026-08-23).',
        );
    }

    public function testDraftClientBodyAndPathMatchTheContract(): void
    {
        $http = new RecordingHttpClient();
        $this->client($http)->createDraftClient('Acme Corp');

        self::assertSame('https://invoice.example/admin/clients', (string) $http->requests[0]->getUri());
        self::assertSame(['name' => 'Acme Corp'], $http->decodedBody(0));
    }

    public function testQuotePathAndAuthHeader(): void
    {
        $http = new RecordingHttpClient();
        $this->client($http)->createDraftQuote(1, 1000, 'x');

        $request = $http->requests[0];
        self::assertSame('https://invoice.example/admin/quotes', (string) $request->getUri());
        self::assertSame('POST', $request->getMethod());
        self::assertSame('Bearer tok', $request->getHeaderLine('Authorization'));
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    /**
     * Positive control for the double itself: the assertions above are only
     * meaningful if a wrong contract can actually reach the transport and be
     * observed. A 4xx must still surface as InvoiceHandoffException — which is
     * what a contract mismatch looks like from Deal's side in production.
     */
    public function testUpstreamRejectionSurfacesAsHandoffException(): void
    {
        $http = new RecordingHttpClient([['status' => 422, 'body' => '{"errors":[]}']]);

        $this->expectException(InvoiceHandoffException::class);
        $this->expectExceptionMessage('HTTP 422');

        $this->client($http)->createDraftQuote(1, 1000, 'x');
    }
}
