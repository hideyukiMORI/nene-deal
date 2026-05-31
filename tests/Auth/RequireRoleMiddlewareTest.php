<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use NeneDeal\Auth\RequireRoleMiddleware;
use NeneDeal\Tests\Support\RecordingRequestHandler;
use NeneDeal\User\OperatorRole;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class RequireRoleMiddlewareTest extends TestCase
{
    private Psr17Factory $psr17;
    private ProblemDetailsResponseFactory $problemDetails;

    protected function setUp(): void
    {
        $this->psr17 = new Psr17Factory();
        $this->problemDetails = new ProblemDetailsResponseFactory($this->psr17, $this->psr17, 'https://nene-deal.dev/problems/');
    }

    private function requestWithRole(?string $role): \Psr\Http\Message\ServerRequestInterface
    {
        $request = $this->psr17->createServerRequest('GET', '/test');

        if ($role !== null) {
            $request = $request->withAttribute('nene2.auth.claims', ['sub' => 'user-id', 'role' => $role]);
        }

        return $request;
    }

    private function middleware(string $requiredRole): RequireRoleMiddleware
    {
        return new RequireRoleMiddleware(OperatorRole::from($requiredRole), $this->problemDetails);
    }

    // --- require 'admin' ---

    public function test_admin_token_passes_to_handler(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);
        $response = $this->middleware('admin')->process($this->requestWithRole('admin'), $handler);

        self::assertTrue($handler->called);
        self::assertSame(200, $response->getStatusCode());
    }

    public function test_operator_token_is_rejected_when_admin_required(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);
        $response = $this->middleware('admin')->process($this->requestWithRole('operator'), $handler);

        self::assertFalse($handler->called);
        self::assertSame(403, $response->getStatusCode());
    }

    public function test_no_claims_attribute_is_rejected_when_admin_required(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);
        $request = $this->psr17->createServerRequest('GET', '/test'); // no claims
        $response = $this->middleware('admin')->process($request, $handler);

        self::assertFalse($handler->called);
        self::assertSame(403, $response->getStatusCode());
    }

    public function test_empty_role_claim_is_rejected(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);
        $request = $this->psr17
            ->createServerRequest('GET', '/test')
            ->withAttribute('nene2.auth.claims', ['sub' => 'x', 'role' => '']);

        $response = $this->middleware('admin')->process($request, $handler);

        self::assertFalse($handler->called);
        self::assertSame(403, $response->getStatusCode());
    }

    public function test_unknown_role_string_is_rejected_when_admin_required(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);
        $response = $this->middleware('admin')->process($this->requestWithRole('superuser'), $handler);

        self::assertFalse($handler->called);
        self::assertSame(403, $response->getStatusCode());
    }

    // --- require 'operator' (any authenticated role passes) ---

    public function test_operator_token_passes_when_operator_required(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);
        $response = $this->middleware('operator')->process($this->requestWithRole('operator'), $handler);

        self::assertTrue($handler->called);
        self::assertSame(200, $response->getStatusCode());
    }

    public function test_admin_token_passes_when_operator_required(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);
        $response = $this->middleware('operator')->process($this->requestWithRole('admin'), $handler);

        self::assertTrue($handler->called);
        self::assertSame(200, $response->getStatusCode());
    }

    public function test_no_claims_rejected_when_operator_required(): void
    {
        $handler = new RecordingRequestHandler($this->psr17);
        $request = $this->psr17->createServerRequest('GET', '/test');
        $response = $this->middleware('operator')->process($request, $handler);

        self::assertFalse($handler->called);
        self::assertSame(403, $response->getStatusCode());
    }
}
