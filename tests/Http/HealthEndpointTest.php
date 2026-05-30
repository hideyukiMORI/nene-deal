<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Http;

use NeneDeal\Http\RuntimeContainerFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * End-to-end smoke test of the consumer bootstrap: building the container
 * resolves the application handler, and `GET /health` returns an OK payload.
 *
 * The scaffold registers no health checks, so the framework returns the basic
 * `{status, service}` shape. Dependency checks (database) arrive in a later
 * issue and will extend this assertion.
 */
final class HealthEndpointTest extends TestCase
{
    public function test_health_reports_ok_when_booted(): void
    {
        $container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();

        $application = $container->get(RequestHandlerInterface::class);
        self::assertInstanceOf(RequestHandlerInterface::class, $application);

        $request = (new Psr17Factory())->createServerRequest('GET', '/health');
        $response = $application->handle($request);

        self::assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);
        self::assertIsArray($payload);
        self::assertSame('ok', $payload['status'] ?? null);
        self::assertArrayHasKey('service', $payload);
    }
}
