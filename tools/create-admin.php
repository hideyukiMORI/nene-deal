<?php

declare(strict_types=1);

/**
 * Creates the first real admin account on a manually deployed box (#109).
 *
 * The no-browser install path (docs/demo.md §5, manual alternative) has no
 * seeded account anymore — the well-known dev operator is opt-in via
 * NENE_DEAL_SEED_DEV_OPERATOR=1 and never present on real deployments. This
 * tool is the CLI counterpart of the browser installer's provisioning step:
 * it reuses {@see \NeneDeal\Install\AdminProvisioner} (renames the seeded
 * organization, removes the dev-seed account if one exists, creates the admin
 * — all in one transaction; the plaintext password lives in memory only).
 *
 * Usage:
 *   NENE_DEAL_ADMIN_PASSWORD=... php tools/create-admin.php <email> [organization-name]
 *   php tools/create-admin.php <email> [organization-name]   # prompts (hidden) for the password
 *
 * Config-boundary entry point: env wiring mirrors tools/seed-demo.php.
 */

use Nene2\Config\ConfigLoader;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Http\UtcClock;
use NeneDeal\Install\AdminProvisioner;

require_once __DIR__ . '/../vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, 'This script must be run from the command line.' . PHP_EOL);
    exit(1);
}

$email = $argv[1] ?? '';
$organizationName = $argv[2] ?? 'NeNe Deal';

if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, 'Usage: php tools/create-admin.php <email> [organization-name]' . PHP_EOL);
    fwrite(STDERR, 'Password: set NENE_DEAL_ADMIN_PASSWORD, or enter it at the hidden prompt.' . PHP_EOL);
    exit(1);
}

$password = $_ENV['NENE_DEAL_ADMIN_PASSWORD'] ?? getenv('NENE_DEAL_ADMIN_PASSWORD');

if (!is_string($password) || $password === '') {
    fwrite(STDOUT, 'Admin password (input hidden): ');
    // Hidden prompt; falls back to visible input where stty is unavailable.
    shell_exec('stty -echo 2>/dev/null');
    $password = trim((string) fgets(STDIN));
    shell_exec('stty echo 2>/dev/null');
    fwrite(STDOUT, PHP_EOL);
}

if (strlen($password) < 8) {
    fwrite(STDERR, 'The admin password must be at least 8 characters.' . PHP_EOL);
    exit(1);
}

$config = (new ConfigLoader(dirname(__DIR__)))->load();
$pdo = (new PdoConnectionFactory($config->database))->create();

try {
    $result = (new AdminProvisioner($pdo, new UtcClock()))->provision($organizationName, $email, $password);
} catch (Throwable $exception) {
    fwrite(STDERR, 'create-admin failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, sprintf(
    "admin created: %s (user %s, organization %s)%s\n",
    $email,
    $result['adminUserId'],
    $result['organizationId'],
    $result['devSeedRemoved'] ? ' — removed the dev-seeded operator account' : '',
));

exit(0);
