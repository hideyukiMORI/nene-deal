<?php

declare(strict_types=1);

/**
 * Demo data seed / reset: fills the target organization with a realistic,
 * presentation-ready pipeline (kanban spread over every stage, a monthly
 * forecast with substance, per-deal stage history) plus dedicated demo
 * operator accounts. Re-running the script IS the reset: it removes the
 * organization's deals (and their activity rows) and reseeds from scratch,
 * leaving users, stages and settings untouched.
 *
 * All dates are relative to the run date so the board always looks like a
 * live pipeline: won deals landed earlier this month, open deals close this
 * month / next month / the month after.
 *
 * Demo credentials (upserted, never duplicated):
 *   demo-admin@nene-deal.test  (admin)
 *   sato@nene-deal.test        (operator)
 *   takahashi@nene-deal.test   (operator)
 *
 * The shared password defaults to "deal-demo" for local use and MUST be
 * overridden via NENE_DEAL_DEMO_PASSWORD in production (fail-close).
 *
 * Usage:
 *   php tools/seed-demo.php [--org=<slug>] [--dry-run]
 *
 * --org defaults to "default" (the migration-seeded organization). The deal
 * dataset itself lives in NeneDeal\Demo\DemoPipelineFixture, shared with the
 * disposable per-visitor demo orgs of the /demo/{template} route (#69).
 */

use Nene2\Config\AppEnvironment;
use Nene2\Config\ConfigLoader;
use Nene2\Database\PdoConnectionFactory;
use NeneDeal\Demo\DemoPipelineFixture;
use Symfony\Component\Uid\Ulid;

require_once __DIR__ . '/../vendor/autoload.php';

// ---------------------------------------------------------------------------
// CLI arguments / environment
// ---------------------------------------------------------------------------

$orgSlug = 'default';
$dryRun = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') {
        $dryRun = true;
    } elseif (str_starts_with($arg, '--org=')) {
        $orgSlug = substr($arg, strlen('--org='));
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Usage: php tools/seed-demo.php [--org=<slug>] [--dry-run]\n";
        exit(0);
    } else {
        fwrite(STDERR, "Unknown argument: {$arg}\n");
        exit(1);
    }
}

if ($orgSlug === '') {
    fwrite(STDERR, "--org must not be empty.\n");
    exit(1);
}

$config = (new ConfigLoader(__DIR__ . '/..'))->load();

$demoPassword = getenv('NENE_DEAL_DEMO_PASSWORD');
$demoPassword = is_string($demoPassword) && $demoPassword !== '' ? $demoPassword : null;

if ($demoPassword === null) {
    if ($config->environment === AppEnvironment::Production) {
        fwrite(STDERR, "NENE_DEAL_DEMO_PASSWORD must be set in production — the built-in default password is refused (fail-close).\n");
        exit(1);
    }

    $demoPassword = 'deal-demo';
}

$pdo = (new PdoConnectionFactory($config->database))->create();

// ---------------------------------------------------------------------------
// Resolve organization and its default stage set
// ---------------------------------------------------------------------------

$stmt = $pdo->prepare('SELECT id FROM organizations WHERE slug = ?');
$stmt->execute([$orgSlug]);
$orgId = $stmt->fetchColumn();

if (!is_string($orgId) || $orgId === '') {
    fwrite(STDERR, "Organization with slug \"{$orgSlug}\" not found. Run migrations first (composer migrations:migrate).\n");
    exit(1);
}

$stmt = $pdo->prepare('SELECT slug, id FROM pipeline_stages WHERE organization_id = ?');
$stmt->execute([$orgId]);

/** @var array<string, string> $stageIdBySlug */
$stageIdBySlug = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $stageIdBySlug[(string) $row['slug']] = (string) $row['id'];
}

foreach (DemoPipelineFixture::STAGE_SLUGS as $required) {
    if (!isset($stageIdBySlug[$required])) {
        fwrite(STDERR, "Stage \"{$required}\" not found in organization \"{$orgSlug}\". The demo seed expects the default stage set.\n");
        exit(1);
    }
}

// ---------------------------------------------------------------------------
// Demo users (upsert by email — reruns never duplicate)
// ---------------------------------------------------------------------------

$demoUsers = [
    ['email' => 'demo-admin@nene-deal.test', 'role' => 'admin'],
    ['email' => 'sato@nene-deal.test', 'role' => 'operator'],
    ['email' => 'takahashi@nene-deal.test', 'role' => 'operator'],
];

// ---------------------------------------------------------------------------
// Deal fixtures — the shared, fully date-resolved dataset (funnel-shaped
// spread, JPY amounts, per-deal timelines). `owner` indexes $demoUsers.
// ---------------------------------------------------------------------------

$today = new DateTimeImmutable('today', new DateTimeZone('UTC'));
$deals = DemoPipelineFixture::deals($today);

// ---------------------------------------------------------------------------
// Dry run: report what would happen, touch nothing
// ---------------------------------------------------------------------------

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM deals WHERE organization_id = ?');
$countStmt->execute([$orgId]);
$existing = (int) $countStmt->fetchColumn();

if ($dryRun) {
    echo "[dry-run] Organization \"{$orgSlug}\": would remove {$existing} existing deal(s) and seed " . count($deals) . " demo deal(s) + " . count($demoUsers) . " demo user(s).\n";
    exit(0);
}

// ---------------------------------------------------------------------------
// Reset + seed (single transaction)
// ---------------------------------------------------------------------------

$pdo->beginTransaction();

try {
    // Reset: deals + their activity rows only. Users, stages and settings stay.
    $pdo->prepare('DELETE FROM deal_stage_history WHERE deal_id IN (SELECT id FROM deals WHERE organization_id = ?)')->execute([$orgId]);
    $pdo->prepare('DELETE FROM deals WHERE organization_id = ?')->execute([$orgId]);

    // Upsert demo users.
    $now = $today->setTime(9, 0)->format('Y-m-d H:i:s');
    $passwordHash = password_hash($demoPassword, PASSWORD_DEFAULT);
    $findUser = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $updateUser = $pdo->prepare('UPDATE users SET organization_id = ?, password_hash = ?, role = ?, updated_at = ? WHERE id = ?');
    $insertUser = $pdo->prepare('INSERT INTO users (id, organization_id, email, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');

    /** @var list<string> $userIds */
    $userIds = [];
    foreach ($demoUsers as $user) {
        $findUser->execute([$user['email']]);
        $id = $findUser->fetchColumn();

        if (is_string($id) && $id !== '') {
            $updateUser->execute([$orgId, $passwordHash, $user['role'], $now, $id]);
        } else {
            $id = (string) new Ulid();
            $insertUser->execute([$id, $orgId, $user['email'], $passwordHash, $user['role'], $now, $now]);
        }

        $userIds[] = $id;
    }

    // Insert deals + activity timelines.
    $insertDeal = $pdo->prepare(
        'INSERT INTO deals (id, organization_id, account_label, amount_cents, stage_id, probability_percent,'
        . ' expected_close_date, owner_user_id, note, created_at, updated_at)'
        . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    );
    $insertActivity = $pdo->prepare(
        'INSERT INTO deal_stage_history (id, deal_id, from_stage_id, to_stage_id, action, changes, actor_user_id, created_at)'
        . ' VALUES (?, ?, ?, ?, ?, NULL, ?, ?)',
    );

    $stageCounts = [];

    foreach ($deals as $deal) {
        $dealId = (string) new Ulid();
        $ownerId = $userIds[$deal['owner']];

        // Deal row first (deal_stage_history.deal_id has an FK on deals.id).
        $insertDeal->execute([
            $dealId,
            $orgId,
            $deal['company'],
            $deal['amount_cents'],
            $stageIdBySlug[$deal['stage']],
            $deal['probability'],
            $deal['close_date'],
            $ownerId,
            $deal['note'],
            $deal['created_at'],
            $deal['updated_at'],
        ]);

        foreach ($deal['timeline'] as $event) {
            $insertActivity->execute([
                (string) new Ulid(),
                $dealId,
                $event['from'] !== null ? $stageIdBySlug[$event['from']] : null,
                $stageIdBySlug[$event['to']],
                $event['action'],
                $ownerId,
                $event['created_at'],
            ]);
        }

        $stageCounts[$deal['stage']] = ($stageCounts[$deal['stage']] ?? 0) + 1;
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();

    throw $e;
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------

echo "Demo seed complete for organization \"{$orgSlug}\" (removed {$existing} existing deal(s)).\n\n";
echo 'Deals: ' . count($deals) . ' — ';
$parts = [];
foreach (DemoPipelineFixture::STAGE_SLUGS as $slug) {
    $parts[] = $slug . ' ' . ($stageCounts[$slug] ?? 0);
}
echo implode(' / ', $parts) . "\n\n";
echo "Demo credentials (shared password" . (getenv('NENE_DEAL_DEMO_PASSWORD') ? ' from NENE_DEAL_DEMO_PASSWORD' : ": \"{$demoPassword}\"") . "):\n";
foreach ($demoUsers as $user) {
    echo "  {$user['email']}  ({$user['role']})\n";
}
echo "\nReset: re-run this command any time — it wipes the organization's deals and reseeds.\n";
exit(0);
