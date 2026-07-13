<?php

declare(strict_types=1);

namespace NeneDeal\Demo;

use LogicException;
use Nene2\Auth\TokenIssuerInterface;
use Nene2\Config\AppConfig;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Demo\CountingDemoCapacityGuard;
use Nene2\Demo\DemoConfig;
use Nene2\Demo\DemoRouteRegistrar;
use Nene2\Demo\StartDisposableDemoHandler;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use NeneDeal\Http\RuntimeServiceProvider;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;

/**
 * Wires the disposable-demo domain as a `Nene2\Demo` consumer (#69): the
 * product concretes (provisioner / seeder / seater / reaper over the shared
 * {@see DemoOrgHandles} ULID↔int registry), the creation-time capacity guard
 * (per-IP file-backed throttle + instance-wide org ceiling), the branded
 * browser error page, and the framework handler + route registrar. No auth
 * code is added — the seater mints a token through the same
 * {@see TokenIssuerInterface} a login uses.
 *
 * The registrar is registered unconditionally: {@see StartDisposableDemoHandler}
 * answers 404 while {@see DemoConfig::$demoMode} is off (fail-close).
 */
final readonly class DemoServiceProvider implements ServiceProviderInterface
{
    /**
     * Demo starts allowed per client network per window (framework default,
     * NENE2 ADR 0018). Deliberately generous — a "client" is really one
     * office/carrier NAT, and runaway abuse stays bounded by the
     * instance-wide org ceiling plus the sweep cron.
     */
    public const int THROTTLE_LIMIT = 30;
    public const int THROTTLE_WINDOW_SECONDS = 3600;

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                DemoConfig::class,
                static function (ContainerInterface $c): DemoConfig {
                    $config = $c->get(AppConfig::class);
                    if (!$config instanceof AppConfig) {
                        throw new LogicException('Application config service is invalid.');
                    }

                    return $config->demo;
                },
            )
            ->set(
                DemoOrgHandles::class,
                static fn (ContainerInterface $c): DemoOrgHandles => new DemoOrgHandles(),
            )
            ->set(
                DemoOrgProvisioner::class,
                static fn (ContainerInterface $c): DemoOrgProvisioner => new DemoOrgProvisioner(
                    self::query($c),
                    self::clock($c),
                    self::handles($c),
                ),
            )
            ->set(
                DemoDataSeeder::class,
                static fn (ContainerInterface $c): DemoDataSeeder => new DemoDataSeeder(
                    self::query($c),
                    self::clock($c),
                    self::handles($c),
                ),
            )
            ->set(
                DemoSessionSeater::class,
                static function (ContainerInterface $c): DemoSessionSeater {
                    $tokenIssuer = $c->get(TokenIssuerInterface::class);
                    if (!$tokenIssuer instanceof TokenIssuerInterface) {
                        throw new LogicException('Token issuer service is invalid.');
                    }

                    return new DemoSessionSeater(
                        $tokenIssuer,
                        self::clock($c),
                        self::handles($c),
                        self::psr17($c),
                        FileDemoEntryLogSink::toFile(self::projectRoot($c) . '/var'),
                    );
                },
            )
            ->set(
                DemoOrgReaper::class,
                static fn (ContainerInterface $c): DemoOrgReaper => new DemoOrgReaper(
                    self::query($c),
                    self::handles($c),
                ),
            )
            ->set(
                CountingDemoCapacityGuard::class,
                static function (ContainerInterface $c): CountingDemoCapacityGuard {
                    $config = self::demoConfig($c);
                    $query = self::query($c);
                    $projectRoot = self::projectRoot($c);

                    return new CountingDemoCapacityGuard(
                        demoOrgCount: static function () use ($query, $config): int {
                            // ESCAPE '|' — a backslash escape char is itself escaped
                            // differently by MySQL string literals vs SQLite (clear #277).
                            $row = $query->fetchOne(
                                "SELECT COUNT(*) AS n FROM organizations WHERE slug LIKE ? ESCAPE '|'",
                                [str_replace(['|', '%', '_'], ['||', '|%', '|_'], $config->slugPrefix) . '%'],
                            );

                            return is_array($row) ? (int) $row['n'] : 0;
                        },
                        config: $config,
                        throttleStorage: new FileRateLimitStorage($projectRoot . '/var', self::clock($c)),
                        throttleLimit: self::THROTTLE_LIMIT,
                        throttleWindowSeconds: self::THROTTLE_WINDOW_SECONDS,
                        clock: self::clock($c),
                    );
                },
            )
            ->set(
                DemoBrowserErrorPage::class,
                static fn (ContainerInterface $c): DemoBrowserErrorPage => new DemoBrowserErrorPage(
                    self::psr17($c),
                    self::THROTTLE_LIMIT,
                    self::THROTTLE_WINDOW_SECONDS,
                ),
            )
            ->set(
                StartDisposableDemoHandler::class,
                static function (ContainerInterface $c): StartDisposableDemoHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);
                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    $capacityGuard = $c->get(CountingDemoCapacityGuard::class);
                    if (!$capacityGuard instanceof CountingDemoCapacityGuard) {
                        throw new LogicException('Demo capacity guard service is invalid.');
                    }

                    $provisioner = $c->get(DemoOrgProvisioner::class);
                    if (!$provisioner instanceof DemoOrgProvisioner) {
                        throw new LogicException('Demo org provisioner service is invalid.');
                    }

                    $seeder = $c->get(DemoDataSeeder::class);
                    if (!$seeder instanceof DemoDataSeeder) {
                        throw new LogicException('Demo data seeder service is invalid.');
                    }

                    $seater = $c->get(DemoSessionSeater::class);
                    if (!$seater instanceof DemoSessionSeater) {
                        throw new LogicException('Demo session seater service is invalid.');
                    }

                    $errorPage = $c->get(DemoBrowserErrorPage::class);
                    if (!$errorPage instanceof DemoBrowserErrorPage) {
                        throw new LogicException('Demo error page renderer service is invalid.');
                    }

                    return new StartDisposableDemoHandler(
                        config: self::demoConfig($c),
                        capacityGuard: $capacityGuard,
                        provisioner: $provisioner,
                        seeder: $seeder,
                        seater: $seater,
                        problemDetails: $problemDetails,
                        templateKeyClass: DemoTemplate::class,
                        errorPageRenderer: $errorPage,
                    );
                },
            )
            ->set(
                DemoRouteRegistrar::class,
                static function (ContainerInterface $c): DemoRouteRegistrar {
                    $handler = $c->get(StartDisposableDemoHandler::class);
                    if (!$handler instanceof StartDisposableDemoHandler) {
                        throw new LogicException('Demo start handler service is invalid.');
                    }

                    return new DemoRouteRegistrar($handler);
                },
            );
    }

    private static function query(ContainerInterface $c): DatabaseQueryExecutorInterface
    {
        $query = $c->get(DatabaseQueryExecutorInterface::class);
        if (!$query instanceof DatabaseQueryExecutorInterface) {
            throw new LogicException('Database query executor service is invalid.');
        }

        return $query;
    }

    private static function clock(ContainerInterface $c): ClockInterface
    {
        $clock = $c->get(ClockInterface::class);
        if (!$clock instanceof ClockInterface) {
            throw new LogicException('Clock service is invalid.');
        }

        return $clock;
    }

    private static function handles(ContainerInterface $c): DemoOrgHandles
    {
        $handles = $c->get(DemoOrgHandles::class);
        if (!$handles instanceof DemoOrgHandles) {
            throw new LogicException('Demo org handles service is invalid.');
        }

        return $handles;
    }

    private static function projectRoot(ContainerInterface $c): string
    {
        $projectRoot = $c->get(RuntimeServiceProvider::PROJECT_ROOT);
        if (!is_string($projectRoot) || $projectRoot === '') {
            throw new LogicException('Project root service is invalid.');
        }

        return $projectRoot;
    }

    private static function psr17(ContainerInterface $c): Psr17Factory
    {
        $psr17 = $c->get(Psr17Factory::class);
        if (!$psr17 instanceof Psr17Factory) {
            throw new LogicException('PSR-17 factory service is invalid.');
        }

        return $psr17;
    }

    private static function demoConfig(ContainerInterface $c): DemoConfig
    {
        $config = $c->get(DemoConfig::class);
        if (!$config instanceof DemoConfig) {
            throw new LogicException('Demo config service is invalid.');
        }

        return $config;
    }
}
