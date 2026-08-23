<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Support;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 client that records every request and replays canned responses.
 *
 * The point of this double is the **request**: {@see HttpInvoiceClient} is the
 * only place that knows the wire shape of the NeNe Invoice admin API, and the
 * `InvoiceClient` interface deliberately hides it (`createDraftQuote()` takes a
 * client id, an amount and a label — no payload). That is why `#212` — three
 * field-name mismatches that made every handoff a guaranteed 422 — could sit
 * undetected behind a green suite: `FakeInvoiceClient` implements the
 * *interface*, so no test in this repo could see a body.
 *
 * Anything asserting the outgoing contract must therefore sit **below** the
 * interface, at the transport.
 */
final class RecordingHttpClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $requests = [];

    /** @var list<array{status: int, body: string}> */
    private array $responses;

    /**
     * @param list<array{status: int, body: string}> $responses replayed in order;
     *        the last one repeats once exhausted.
     */
    public function __construct(array $responses = [['status' => 201, 'body' => '{"id":1}']])
    {
        $this->responses = $responses;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        $canned = $this->responses[count($this->requests) - 1] ?? $this->responses[count($this->responses) - 1];

        $psr17 = new Psr17Factory();

        return $psr17->createResponse($canned['status'])
            ->withBody($psr17->createStream($canned['body']));
    }

    /**
     * Decoded JSON body of the recorded request at `$index`.
     *
     * @return array<string, mixed>
     */
    public function decodedBody(int $index): array
    {
        $body = (string) $this->requests[$index]->getBody();
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }
}
