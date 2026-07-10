<?php

declare(strict_types=1);

namespace NeneDeal\Install;

/**
 * Builds the ordered `KEY => value` map the web installer writes to `.env`.
 *
 * Extracted out of `install.php` so the exact set/order of persisted keys is a
 * single, unit-tested seam (same shape as nene-vault's InstallEnvironment). The
 * map is handed to {@see \Nene2\Install\EnvironmentWriter}, which restricts the
 * file to 0640 (fail-closed) and escapes the values — so a DB password or JWT
 * secret containing spaces, quotes, `#` or `$` survives a round-trip and no
 * value can inject an extra `.env` line.
 *
 * The admin password and admin email are deliberately NOT part of this map:
 * the account is provisioned in-process by {@see AdminProvisioner}, so neither
 * needs to rest in `.env`. The organization name lives in the database, not in
 * the environment.
 */
final class InstallEnvironment
{
    /**
     * @param array{adapter?: string, host?: string, port?: string, name?: string, user?: string, password?: string} $db
     *
     * @return array<string, string>
     */
    public static function values(string $jwtSecret, array $db): array
    {
        $adapter = ($db['adapter'] ?? 'mysql') === 'sqlite' ? 'sqlite' : 'mysql';

        return [
            'APP_ENV'                  => 'production',
            'APP_DEBUG'                => 'false',
            'APP_NAME'                 => 'NeNe Deal',
            'PROBLEM_DETAILS_BASE_URL' => 'https://nene-deal.dev/problems/',
            'NENE2_LOCAL_JWT_SECRET'   => $jwtSecret,
            'DB_ENV'                   => 'production',
            'DB_ADAPTER'               => $adapter,
            'DB_HOST'                  => $db['host'] ?? '127.0.0.1',
            'DB_PORT'                  => $db['port'] ?? '3306',
            'DB_NAME'                  => $adapter === 'sqlite'
                ? (($db['name'] ?? '') !== '' ? (string) $db['name'] : 'var/nene_deal.sqlite')
                : ($db['name'] ?? 'nene_deal'),
            'DB_USER'                  => $db['user'] ?? '',
            'DB_PASSWORD'              => $db['password'] ?? '',
            'DB_CHARSET'               => 'utf8mb4',
        ];
    }
}
