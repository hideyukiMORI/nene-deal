<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Http;

use LogicException;
use Nene2\Config\AppConfig;
use Nene2\Config\AppEnvironment;
use Nene2\Config\DatabaseConfig;
use NeneDeal\Http\RuntimeServiceProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * The local JWT secret must fail closed in production when NENE2_LOCAL_JWT_SECRET
 * is unset (or empty), rather than falling back to the public dev constant (which
 * would let anyone forge a superadmin token). Off-production the dev fallback is
 * allowed so login works without configuration.
 */
final class JwtSecretResolutionTest extends TestCase
{
    public function test_production_without_secret_refuses_to_boot(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('NENE2_LOCAL_JWT_SECRET');

        self::resolve(self::config(AppEnvironment::Production, null));
    }

    public function test_production_with_empty_secret_refuses_to_boot(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('NENE2_LOCAL_JWT_SECRET');

        self::resolve(self::config(AppEnvironment::Production, ''));
    }

    public function test_production_with_secret_uses_it(): void
    {
        self::assertSame(
            'a-real-production-secret',
            self::resolve(self::config(AppEnvironment::Production, 'a-real-production-secret')),
        );
    }

    public function test_local_without_secret_falls_back_to_dev_constant(): void
    {
        // Local/test convenience: the dev fallback is allowed only off-production.
        self::assertNotSame('', self::resolve(self::config(AppEnvironment::Local, null)));
    }

    private static function resolve(AppConfig $config): string
    {
        $method = new ReflectionMethod(RuntimeServiceProvider::class, 'resolveJwtSecret');

        /** @var string $secret */
        $secret = $method->invoke(null, $config);

        return $secret;
    }

    private static function config(AppEnvironment $env, ?string $secret): AppConfig
    {
        return new AppConfig(
            $env,
            false,
            'NeNe Deal',
            new DatabaseConfig(null, 'test', 'sqlite', '', 1, ':memory:', '', '', ''),
            null,
            $secret,
        );
    }
}
