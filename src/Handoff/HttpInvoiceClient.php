<?php

declare(strict_types=1);

namespace NeneDeal\Handoff;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;

/**
 * PSR-18 implementation of {@see InvoiceClient}. Talks to the NeNe Invoice admin
 * API over HTTP. Deal creates draft resources only; exact paths and payloads
 * follow the NeNe Invoice OpenAPI and may be adjusted when integrating.
 *
 * Any transport, configuration, or non-2xx condition is wrapped in
 * {@see InvoiceHandoffException} so the deal is left unlinked.
 */
final readonly class HttpInvoiceClient implements InvoiceClient
{
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
        $response = $this->post('/admin/quotes', [
            'client_id' => $clientId,
            'currency' => 'JPY',
            'lines' => [
                ['description' => $accountLabel, 'unit_amount_cents' => $amountCents, 'quantity' => 1],
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
