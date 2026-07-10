<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Demo;

use Nene2\Auth\TokenIssuerInterface;
use Nene2\Demo\CountingDemoCapacityGuard;
use Nene2\Demo\DemoConfig;
use Nene2\Demo\StartDisposableDemoHandler;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Routing\Router;
use NeneDeal\Demo\DemoBrowserErrorPage;
use NeneDeal\Demo\DemoDataSeeder;
use NeneDeal\Demo\DemoOrgProvisioner;
use NeneDeal\Demo\DemoSessionSeater;
use NeneDeal\Demo\FileRateLimitStorage;
use NeneDeal\Tests\Support\FixedClock;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Uid\Ulid;

/**
 * End-to-end coverage of `GET /demo/{template}` through the framework
 * orchestrator wired with Deal's concretes: the fail-close gate, the
 * throttle / capacity guards (HTML-negotiated for browsers), and the happy
 * path (provision → seed → seat).
 */
final class StartDemoFlowTest extends DemoDatabaseTestCase
{
    private string $rateLimitDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimitDir = sys_get_temp_dir() . '/nene-deal-demo-flow-' . bin2hex(random_bytes(6));
        mkdir($this->rateLimitDir, 0o775, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->rateLimitDir . '/rate-limits/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->rateLimitDir . '/rate-limits');
        @rmdir($this->rateLimitDir);
    }

    private function handler(DemoConfig $config, int $throttleLimit = 30): StartDisposableDemoHandler
    {
        $clock = new FixedClock();
        $psr17 = new Psr17Factory();
        $problemDetails = new ProblemDetailsResponseFactory($psr17, $psr17, 'https://nene-deal.dev/problems/');

        $issuer = new class () implements TokenIssuerInterface {
            public function issue(array $claims): string
            {
                return 'flow-test-token';
            }
        };

        $guard = new CountingDemoCapacityGuard(
            demoOrgCount: fn (): int => $this->countRows(
                "SELECT COUNT(*) FROM organizations WHERE slug LIKE ? ESCAPE '|'",
                [str_replace(['|', '%', '_'], ['||', '|%', '|_'], $config->slugPrefix) . '%'],
            ),
            config: $config,
            throttleStorage: new FileRateLimitStorage($this->rateLimitDir, $clock),
            throttleLimit: $throttleLimit,
            throttleWindowSeconds: 3600,
            clock: $clock,
        );

        return new StartDisposableDemoHandler(
            config: $config,
            capacityGuard: $guard,
            provisioner: new DemoOrgProvisioner($this->query, $clock, $this->handles),
            seeder: new DemoDataSeeder($this->query, $clock, $this->handles),
            seater: new DemoSessionSeater($issuer, $clock, $this->handles, $psr17),
            problemDetails: $problemDetails,
            templateKeyClass: \NeneDeal\Demo\DemoTemplate::class,
            errorPageRenderer: new DemoBrowserErrorPage($psr17, 30, 3600),
        );
    }

    private function request(string $template, string $accept = 'application/json'): ServerRequestInterface
    {
        return (new Psr17Factory())
            ->createServerRequest('GET', '/demo/' . $template, ['REMOTE_ADDR' => '203.0.113.7'])
            ->withHeader('Accept', $accept)
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['template' => $template]);
    }

    public function test_demo_mode_off_answers_a_plain_404(): void
    {
        $response = $this->handler(new DemoConfig(demoMode: false))->handle($this->request('standard'));

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('application/problem+json', $response->getHeaderLine('Content-Type'));
    }

    public function test_demo_mode_off_shows_the_branded_page_to_browsers(): void
    {
        $response = $this->handler(new DemoConfig(demoMode: false))
            ->handle($this->request('standard', 'text/html,application/xhtml+xml'));

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        self::assertSame('noindex', $response->getHeaderLine('X-Robots-Tag'));
        self::assertStringContainsString('NeNe Deal', (string) $response->getBody());
    }

    public function test_unknown_template_is_404(): void
    {
        $response = $this->handler(new DemoConfig(demoMode: true))->handle($this->request('nonsense'));

        self::assertSame(404, $response->getStatusCode());
    }

    public function test_throttle_answers_429_with_retry_after_and_html_countdown(): void
    {
        $handler = $this->handler(new DemoConfig(demoMode: true), throttleLimit: 1);

        $first = $handler->handle($this->request('standard'));
        self::assertSame(200, $first->getStatusCode());

        $second = $handler->handle($this->request('standard', 'text/html'));
        self::assertSame(429, $second->getStatusCode());
        self::assertNotSame('', $second->getHeaderLine('Retry-After'));
        self::assertStringContainsString('text/html', $second->getHeaderLine('Content-Type'));
        self::assertStringContainsString('デモのご利用が集中しています', (string) $second->getBody());
        self::assertStringContainsString('id="clock"', (string) $second->getBody());
    }

    public function test_capacity_ceiling_answers_503(): void
    {
        // One pre-existing demo org and a ceiling of one.
        $now = '2026-06-01 00:00:00';
        $this->query->execute(
            'INSERT INTO organizations (id, slug, name, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [(string) new Ulid(), 'demo-existing', 'Old Demo', $now, $now],
        );

        $response = $this->handler(new DemoConfig(demoMode: true, maxOrgs: 1))->handle($this->request('standard'));

        self::assertSame(503, $response->getStatusCode());
    }

    public function test_happy_path_provisions_seeds_and_seats(): void
    {
        $response = $this->handler(new DemoConfig(demoMode: true))->handle($this->request('standard', 'text/html'));

        self::assertSame(200, $response->getStatusCode());
        $html = (string) $response->getBody();
        self::assertStringContainsString('nene-deal-demo-seat', $html);
        self::assertStringContainsString('flow-test-token', $html);

        // Exactly one demo-prefixed org exists, fully seeded.
        $org = $this->query->fetchOne("SELECT id, slug FROM organizations WHERE slug LIKE 'demo-%'");
        self::assertIsArray($org);
        self::assertSame(15, $this->countRows('SELECT COUNT(*) FROM deals WHERE organization_id = ?', [$org['id']]));
        self::assertSame(6, $this->countRows('SELECT COUNT(*) FROM pipeline_stages WHERE organization_id = ?', [$org['id']]));
        self::assertSame(3, $this->countRows('SELECT COUNT(*) FROM users WHERE organization_id = ?', [$org['id']]));
    }

    public function test_each_visit_gets_a_brand_new_org(): void
    {
        $handler = $this->handler(new DemoConfig(demoMode: true));

        $handler->handle($this->request('standard'));
        $handler->handle($this->request('standard'));

        self::assertSame(2, $this->countRows("SELECT COUNT(*) FROM organizations WHERE slug LIKE 'demo-%'"));
    }
}
