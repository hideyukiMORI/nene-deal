<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Install;

use Dotenv\Dotenv;
use Nene2\Install\EnvironmentWriter;
use NeneDeal\Install\InstallEnvironment;
use PHPUnit\Framework\TestCase;

/**
 * Proves the installer's `.env` is written through the NENE2 EnvironmentWriter:
 * restricted to 0640 (not world-readable) and with values escaped so a password
 * containing shell/`.env` metacharacters cannot leak or inject extra lines. Also
 * proves the admin credentials never rest in `.env` — the account is provisioned
 * in-process and the plaintext password only lives in memory.
 */
final class InstallEnvironmentTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/deal-install-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0770, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dir);
    }

    public function test_env_file_is_not_world_readable(): void
    {
        $path = $this->dir . '/.env';

        (new EnvironmentWriter())->write($path, InstallEnvironment::values(
            jwtSecret: EnvironmentWriter::generateSecret(32),
            db: ['adapter' => 'sqlite', 'name' => $this->dir . '/var/nene_deal.sqlite'],
        ));

        $perms = fileperms($path) & 0777;
        self::assertSame(0, $perms & 0007, 'The .env file must not be world-readable.');
        self::assertSame(0640, $perms);
    }

    public function test_admin_credentials_are_never_persisted_to_env(): void
    {
        $values = InstallEnvironment::values(
            jwtSecret: 'deadbeef',
            db: ['adapter' => 'sqlite', 'name' => 'var/nene_deal.sqlite'],
        );

        self::assertArrayNotHasKey('ADMIN_PASSWORD', $values);
        self::assertArrayNotHasKey('ADMIN_EMAIL', $values);
        self::assertArrayHasKey('NENE2_LOCAL_JWT_SECRET', $values);
        self::assertSame('production', $values['APP_ENV']);
        self::assertSame('false', $values['APP_DEBUG']);
        self::assertSame('production', $values['DB_ENV']);
    }

    public function test_password_with_metacharacters_round_trips_without_injection(): void
    {
        $path = $this->dir . '/.env';
        $nasty = 'a"b $c #d e';

        (new EnvironmentWriter())->write($path, InstallEnvironment::values(
            jwtSecret: 'deadbeef',
            db: ['adapter' => 'mysql', 'host' => 'db', 'port' => '3306', 'name' => 'deal', 'user' => 'u', 'password' => $nasty],
        ));

        $parsed = Dotenv::parse((string) file_get_contents($path));

        self::assertSame($nasty, $parsed['DB_PASSWORD']);
        self::assertSame('deal', $parsed['DB_NAME']);
        self::assertSame('mysql', $parsed['DB_ADAPTER']);
    }

    public function test_newline_in_a_value_is_refused(): void
    {
        $this->expectException(\RuntimeException::class);

        (new EnvironmentWriter())->write($this->dir . '/.env', InstallEnvironment::values(
            jwtSecret: 'deadbeef',
            db: ['adapter' => 'mysql', 'name' => 'deal', 'user' => 'u', 'password' => "evil\nDB_HOST=attacker"],
        ));
    }

    public function test_sqlite_name_defaults_when_missing(): void
    {
        $values = InstallEnvironment::values(jwtSecret: 's', db: ['adapter' => 'sqlite']);

        self::assertSame('var/nene_deal.sqlite', $values['DB_NAME']);
        self::assertSame('sqlite', $values['DB_ADAPTER']);
    }
}
