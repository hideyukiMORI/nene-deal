<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Http;

use Nene2\Config\AppConfig;
use NeneDeal\Http\DemoAnalyticsInjection;
use NeneDeal\Http\RuntimeContainerFactory;
use PHPUnit\Framework\TestCase;

/**
 * Regression guard for bug A (#114): the front controller's SPA-shell branch
 * reads DEMO_ANALYTICS_ENDPOINT from $_ENV, but on shared hosting (HETEML) that
 * value lives only in the project `.env` — which is loaded into $_ENV as a side
 * effect of resolving the framework config (NENE2 ConfigLoader::load() →
 * loadDotenvIfAvailable() → Dotenv safeLoad). Docker/CI pass it as a process env
 * var, so the missing config resolution stayed invisible there and the beacon
 * silently never fired in production.
 *
 * This test mirrors the fixed public_html/index.php sequence: resolving
 * AppConfig from the container first loads the project `.env` into $_ENV, after
 * which DemoAnalyticsInjection::fromEnv($_ENV) is enabled. The "before" assertion
 * documents why the pre-fix shell branch (which never resolved config) left the
 * injection disabled on a `.env`-only host.
 */
final class ShellAnalyticsConfigResolutionTest extends TestCase
{
    private const string KEY = 'DEMO_ANALYTICS_ENDPOINT';
    private const string ENDPOINT = 'https://stats.example.test';

    /** @var array{env: ?string, server: ?string, getenv: string|false} */
    private array $prior;

    protected function setUp(): void
    {
        // Snapshot and clear the key across every source Dotenv consults, so the
        // immutable loader (which never overwrites an already-set value) actually
        // populates it from our temp `.env`.
        $this->prior = [
            'env' => is_string($_ENV[self::KEY] ?? null) ? $_ENV[self::KEY] : null,
            'server' => is_string($_SERVER[self::KEY] ?? null) ? $_SERVER[self::KEY] : null,
            'getenv' => getenv(self::KEY),
        ];

        unset($_ENV[self::KEY], $_SERVER[self::KEY]);
        putenv(self::KEY);
    }

    protected function tearDown(): void
    {
        unset($_ENV[self::KEY], $_SERVER[self::KEY]);
        putenv(self::KEY);

        if ($this->prior['env'] !== null) {
            $_ENV[self::KEY] = $this->prior['env'];
        }
        if ($this->prior['server'] !== null) {
            $_SERVER[self::KEY] = $this->prior['server'];
        }
        if ($this->prior['getenv'] !== false) {
            putenv(self::KEY . '=' . $this->prior['getenv']);
        }
    }

    public function test_resolving_app_config_loads_dotenv_endpoint_so_injection_enables(): void
    {
        $projectRoot = sys_get_temp_dir() . '/deal-shell-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($projectRoot, 0o700));

        try {
            file_put_contents($projectRoot . '/.env', self::KEY . '=' . self::ENDPOINT . "\n");

            // Before config resolution the endpoint is not visible — the shell
            // would serve byte-identically with no beacon (the pre-fix bug).
            self::assertFalse(
                DemoAnalyticsInjection::fromEnv($_ENV)->isEnabled(),
                'endpoint must not be visible before AppConfig is resolved',
            );

            // Fixed front-controller sequence: resolve AppConfig, which loads the
            // project `.env` into $_ENV before the shell branch reads it.
            $container = (new RuntimeContainerFactory($projectRoot))->create();
            $config = $container->get(AppConfig::class);
            self::assertInstanceOf(AppConfig::class, $config);

            self::assertSame(self::ENDPOINT, $_ENV[self::KEY] ?? null);
            self::assertTrue(
                DemoAnalyticsInjection::fromEnv($_ENV)->isEnabled(),
                'endpoint from `.env` must enable the beacon once AppConfig is resolved',
            );
        } finally {
            @unlink($projectRoot . '/.env');
            @rmdir($projectRoot);
        }
    }
}
