<?php

declare(strict_types=1);

namespace NeneDeal\Handoff;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;

/**
 * PSR-18 implementation of {@see InvoiceClient}. Talks to the NeNe Invoice admin
 * API over HTTP. Deal creates draft resources only.
 *
 * 🔴 **This class is the only place that knows Invoice's wire shape.** The
 * `InvoiceClient` interface hides it by design, so nothing above this line can
 * assert it — which is how `#212` survived: three wrong field names, every
 * handoff a guaranteed 422, and a green suite the whole time. The payloads
 * below are now pinned field-by-field in `HttpInvoiceClientTest`; do not
 * "adjust them when integrating" without moving that test with them.
 *
 * ⚠️ That test proves Deal matches the contract **as measured on 2026-08-23**.
 * It cannot see Invoice changing it. The live check is QA `#168` A-3.
 *
 * Any transport, configuration, or non-2xx condition is wrapped in
 * {@see InvoiceHandoffException} so the deal is left unlinked.
 */
final readonly class HttpInvoiceClient implements InvoiceClient
{
    /**
     * 🔴 **Provisional. Do not read this as a specification.** Japan's
     * consumption tax has moved 3 → 5 → 8 → 10 % and a reduced rate now runs
     * alongside it, so a hard-coded rate is wrong as a permanent answer even
     * while it is right today. The permanent fix is tracked on the board as
     * **"税率を変更可能にする"** (search that phrase, not a line number — the
     * board renumbers) and is owner-sequenced to start **after** the fleet
     * `nene2-ui` work, because it touches quotes, invoices, existing rows, and
     * how a rate change applies retroactively.
     *
     * 🔴 **This is not Deal's tax policy either.** Deal does not calculate,
     * derive, or classify tax — that is Invoice's domain (AGENTS.md). Do not
     * look here for "Deal's default tax rate"; there is no such thing.
     *
     * It exists only because Invoice's contract has no way to say "unset":
     * `tax_rate_bps` is required on every line item and
     * `CreateQuoteUseCase::ALLOWED_TAX_RATES_BPS` accepts **only 800 or 1000**
     * (measured against `nene-invoice` `origin/main`, 2026-08-23). Omitting it
     * is a 422 and 0 is not a neutral value — it is a rejected one. Of the two,
     * 800 is the reduced rate (food, newspapers), which a B2B deal pipeline
     * never sells; 1000 is what remains. It rides on a **draft** quote, so the
     * Invoice operator sees and edits it before anything reaches a customer.
     *
     * 🏁 **Under the permanent fix this constant disappears entirely**: Invoice
     * holds the allowed and default rates per organization, `tax_rate_bps`
     * becomes optional, and Deal stops sending a rate at all. Delete it then —
     * do not migrate it into a Deal-side setting, which would move the tax
     * domain into the wrong repo.
     */
    private const INVOICE_CONTRACT_STANDARD_TAX_RATE_BPS = 1000;

    public function __construct(
        private ClientInterface $httpClient,
        private Psr17Factory $psr17,
        private string $baseUrl,
        private ?string $bearerToken,
    ) {
    }

    public function createDraftClient(string $accountLabel): int
    {
        $response = $this->post('/admin/clients', ['name' => $accountLabel]);

        return $this->extractId($response);
    }

    public function createDraftQuote(int $clientId, int $amountCents, string $accountLabel): int
    {
        // Field names and shape are Invoice's contract, not ours: `line_items`
        // (not `lines`) and `unit_price_cents` (not `unit_amount_cents`), each
        // line carrying `tax_rate_bps` — see LineItemRequest::parseLines. `#212`
        // sat here as three name mismatches that made every handoff a 422.
        // `currency` is deliberately absent: CreateQuoteHandler never reads it.
        $response = $this->post('/admin/quotes', [
            'client_id' => $clientId,
            'line_items' => [
                [
                    'description' => $accountLabel,
                    'quantity' => 1,
                    'unit_price_cents' => $amountCents,
                    'tax_rate_bps' => self::INVOICE_CONTRACT_STANDARD_TAX_RATE_BPS,
                ],
            ],
        ]);

        return $this->extractId($response);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        if (trim($this->baseUrl) === '') {
            throw new InvoiceHandoffException('NeNe Invoice base URL is not configured (NENE_DEAL_INVOICE_BASE_URL).');
        }

        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        $request = $this->psr17->createRequest('POST', rtrim($this->baseUrl, '/') . $path)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withBody($this->psr17->createStream($body));

        if ($this->bearerToken !== null && $this->bearerToken !== '') {
            $request = $request->withHeader('Authorization', 'Bearer ' . $this->bearerToken);
        }

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            throw new InvoiceHandoffException('NeNe Invoice request failed: ' . $exception->getMessage(), 0, $exception);
        }

        $status = $response->getStatusCode();

        if ($status < 200 || $status >= 300) {
            throw new InvoiceHandoffException(sprintf('NeNe Invoice returned HTTP %d for %s.', $status, $path));
        }

        $decoded = json_decode((string) $response->getBody(), true);

        if (!is_array($decoded)) {
            throw new InvoiceHandoffException(sprintf('NeNe Invoice returned a non-object body for %s.', $path));
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /** @param array<string, mixed> $response */
    private function extractId(array $response): int
    {
        $id = $response['id'] ?? null;

        if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
            throw new InvoiceHandoffException('NeNe Invoice response did not contain a numeric "id".');
        }

        return (int) $id;
    }
}
