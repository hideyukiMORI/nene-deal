<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Tenancy;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneDeal\Tenancy\RequestOrganizationMiddleware;
use NeneDeal\Tests\Support\FakeOrganizationResolver;
use NeneDeal\Tests\Support\RecordingRequestHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class RequestOrganizationMiddlewareTest extends TestCase
{
    private Psr17Factory $psr17;
    /** @var RequestScopedHolder<string> */
    private RequestScopedHolder $holder;

    protected function setUp(): void
    {
        $this->psr17 = new Psr17Factory();
        $this->holder = new RequestScopedHolder();
    }

    private function middleware(FakeOrganizationResolver $resolver): RequestOrganizationMiddleware
    {
        $problemDetails = new ProblemDetailsResponseFactory($this->psr17, $this->psr17, 'https://nene-deal.dev/problems/');

        return new RequestOrganizationMiddleware($this->holder, $resolver, $problemDetails);
    }

    private function handler(): RecordingRequestHandler
    {
        return new RecordingRequestHandler($this->psr17);
    }

    public function test_resolves_organization_from_header_slug(): void
    {
        $resolver = new FakeOrganizationResolver(bySlug: ['acme' => '01ORGACME000000000000000AA']);
        $handler = $this->handler();

        $request = $this->psr17
            ->createServerRequest('GET', '/stages')
            ->withHeader(RequestOrganizationMiddleware::HEADER, 'acme');

        $response = $this->middleware($resolver)->process($request, $handler);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($handler->called);
        self::assertSame('01ORGACME000000000000000AA', $this->holder->get());
    }

    public function test_unknown_slug_returns_404_without_calling_handler(): void
    {
        $resolver = new FakeOrganizationResolver();
        $handler = $this->handler();

        $request = $this->psr17
            ->createServerRequest('GET', '/stages')
            ->withHeader(RequestOrganizationMiddleware::HEADER, 'missing');

        $response = $this->middleware($resolver)->process($request, $handler);

        self::assertSame(404, $response->getStatusCode());
        self::assertFalse($handler->called);
        self::assertFalse($this->holder->isSet());
    }

    public function test_falls_back_to_sole_organization_without_header(): void
    {
        $resolver = new FakeOrganizationResolver(soleId: '01ORGSOLE000000000000000AA');
        $handler = $this->handler();

        $request = $this->psr17->createServerRequest('GET', '/stages');
        $response = $this->middleware($resolver)->process($request, $handler);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($handler->called);
        self::assertSame('01ORGSOLE000000000000000AA', $this->holder->get());
    }

    public function test_leaves_holder_unset_when_no_header_and_no_sole_organization(): void
    {
        $resolver = new FakeOrganizationResolver();
        $handler = $this->handler();

        $request = $this->psr17->createServerRequest('GET', '/health');
        $response = $this->middleware($resolver)->process($request, $handler);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($handler->called);
        self::assertFalse($this->holder->isSet());
    }

    public function test_resolves_organization_from_verified_token_claim(): void
    {
        $resolver = new FakeOrganizationResolver(existingIds: ['01ORGCLAIM00000000000000AA']);
        $handler = $this->handler();

        $request = $this->psr17
            ->createServerRequest('GET', '/api/v1/deals')
            ->withAttribute('nene2.auth.claims', ['sub' => 'u1', 'role' => 'admin', 'org' => '01ORGCLAIM00000000000000AA']);

        $response = $this->middleware($resolver)->process($request, $handler);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($handler->called);
        self::assertSame('01ORGCLAIM00000000000000AA', $this->holder->get());
    }

    public function test_token_claim_wins_over_header_and_sole_fallback(): void
    {
        // The signed claim is authoritative: a header naming another org must
        // not re-scope an authenticated session (demo tenants, #69).
        $resolver = new FakeOrganizationResolver(
            bySlug: ['other' => '01ORGOTHER00000000000000AA'],
            soleId: '01ORGSOLE000000000000000AA',
            existingIds: ['01ORGCLAIM00000000000000AA'],
        );
        $handler = $this->handler();

        $request = $this->psr17
            ->createServerRequest('GET', '/api/v1/deals')
            ->withHeader(RequestOrganizationMiddleware::HEADER, 'other')
            ->withAttribute('nene2.auth.claims', ['org' => '01ORGCLAIM00000000000000AA']);

        $response = $this->middleware($resolver)->process($request, $handler);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('01ORGCLAIM00000000000000AA', $this->holder->get());
    }

    public function test_claim_for_a_vanished_organization_returns_404(): void
    {
        // e.g. a disposable demo org reaped while its token was still alive.
        $resolver = new FakeOrganizationResolver(soleId: '01ORGSOLE000000000000000AA');
        $handler = $this->handler();

        $request = $this->psr17
            ->createServerRequest('GET', '/api/v1/deals')
            ->withAttribute('nene2.auth.claims', ['org' => '01ORGGONE000000000000000AA']);

        $response = $this->middleware($resolver)->process($request, $handler);

        self::assertSame(404, $response->getStatusCode());
        self::assertFalse($handler->called);
        self::assertFalse($this->holder->isSet());
    }

    public function test_claims_without_org_fall_through_to_header_and_sole(): void
    {
        $resolver = new FakeOrganizationResolver(soleId: '01ORGSOLE000000000000000AA');
        $handler = $this->handler();

        $request = $this->psr17
            ->createServerRequest('GET', '/api/v1/deals')
            ->withAttribute('nene2.auth.claims', ['sub' => 'machine']);

        $response = $this->middleware($resolver)->process($request, $handler);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('01ORGSOLE000000000000000AA', $this->holder->get());
    }
}
