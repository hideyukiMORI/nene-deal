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
 * --org defaults to "default" (the migration-seeded organization). Pointing
 * it at another slug keeps the tool reusable for per-demo disposable
 * organizations later.
 */

use Nene2\Config\AppEnvironment;
use Nene2\Config\ConfigLoader;
use Nene2\Database\PdoConnectionFactory;
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

foreach (['lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost'] as $required) {
    if (!isset($stageIdBySlug[$required])) {
        fwrite(STDERR, "Stage \"{$required}\" not found in organization \"{$orgSlug}\". The demo seed expects the default stage set.\n");
        exit(1);
    }
}

// ---------------------------------------------------------------------------
// Date helpers — everything is relative to the run date (UTC)
// ---------------------------------------------------------------------------

$today = new DateTimeImmutable('today', new DateTimeZone('UTC'));

/** Timestamp string N days before today, at a plausible working hour. */
$daysAgo = static function (int $days, int $hour = 10, int $minute = 0) use ($today): string {
    return $today->modify("-{$days} days")->setTime($hour, $minute)->format('Y-m-d H:i:s');
};

/**
 * A close date pinned to a day (1–28) of the month `monthOffset` months from
 * the current one, so the forecast month buckets are stable no matter when
 * the seed runs.
 */
$closeDate = static function (int $monthOffset, int $day) use ($today): string {
    $month = $today->modify('first day of this month');
    if ($monthOffset !== 0) {
        $month = $month->modify(sprintf('%+d months', $monthOffset));
    }

    return $month->setDate((int) $month->format('Y'), (int) $month->format('n'), min(max($day, 1), 28))->format('Y-m-d');
};

/** A close day this month that is guaranteed to be <= today (for won/lost). */
$landedDay = static function (int $daysBack) use ($today): int {
    return max(1, (int) $today->format('j') - $daysBack);
};

// ---------------------------------------------------------------------------
// Demo users (upsert by email — reruns never duplicate)
// ---------------------------------------------------------------------------

$demoUsers = [
    ['email' => 'demo-admin@nene-deal.test', 'role' => 'admin'],
    ['email' => 'sato@nene-deal.test', 'role' => 'operator'],
    ['email' => 'takahashi@nene-deal.test', 'role' => 'operator'],
];

// ---------------------------------------------------------------------------
// Deal fixtures — funnel-shaped spread (lead-heavy → few terminal), amounts in
// the hundreds-of-thousands to millions JPY range, probabilities matching the
// stage, close dates spread across this month (m0), next (m1) and after (m2).
// `owner` indexes $demoUsers. `path` is the stage journey used to generate the
// activity timeline; `created` is days before today.
// ---------------------------------------------------------------------------

$yen = static fn (int $yenAmount): int => $yenAmount * 100; // integer JPY minor units

$deals = [
    // --- lead (4) ---------------------------------------------------------
    ['company' => '有限会社高橋電設', 'note' => '事務所・倉庫の照明LED化改修。紹介案件、来週初回訪問予定。', 'stage' => 'lead', 'amount' => $yen(620000), 'prob' => 10, 'close' => $closeDate(1, 20), 'created' => 4, 'owner' => 1, 'path' => ['lead']],
    ['company' => '株式会社北斗商事', 'note' => '基幹システム更改の情報収集段階。予算感のヒアリングから。', 'stage' => 'lead', 'amount' => $yen(5400000), 'prob' => 10, 'close' => $closeDate(2, 25), 'created' => 6, 'owner' => 2, 'path' => ['lead']],
    ['company' => '株式会社フジイ塗装', 'note' => '見積管理ツールの引き合い。展示会で名刺交換、資料送付済み。', 'stage' => 'lead', 'amount' => $yen(350000), 'prob' => 20, 'close' => $closeDate(2, 10), 'created' => 8, 'owner' => 1, 'path' => ['lead']],
    ['company' => '株式会社カネコ金属', 'note' => '工場換気設備の更新。現場下見の日程調整中。', 'stage' => 'lead', 'amount' => $yen(540000), 'prob' => 20, 'close' => $closeDate(0, 26), 'created' => 3, 'owner' => 0, 'path' => ['lead']],
    // --- qualified (3) ----------------------------------------------------
    ['company' => '株式会社青葉製作所', 'note' => '生産ライン保守契約（年間）。決裁者と面談済み、要件整理中。', 'stage' => 'qualified', 'amount' => $yen(1440000), 'prob' => 35, 'close' => $closeDate(1, 15), 'created' => 14, 'owner' => 2, 'path' => ['lead', 'qualified']],
    ['company' => '中央メディカルサービス株式会社', 'note' => '予約システム保守の乗り換え検討。現行契約は再来月末まで。', 'stage' => 'qualified', 'amount' => $yen(864000), 'prob' => 35, 'close' => $closeDate(0, 24), 'created' => 12, 'owner' => 1, 'path' => ['lead', 'qualified']],
    ['company' => '湘南ハウジング株式会社', 'note' => 'モデルハウスのスマートホーム対応。予算枠は確認済み。', 'stage' => 'qualified', 'amount' => $yen(980000), 'prob' => 40, 'close' => $closeDate(2, 15), 'created' => 18, 'owner' => 0, 'path' => ['lead', 'qualified']],
    // --- proposal (3) -----------------------------------------------------
    ['company' => '大和田印刷株式会社', 'note' => '会社案内・営業ツール一式のリニューアル。提案書提出済み、反応良好。', 'stage' => 'proposal', 'amount' => $yen(480000), 'prob' => 55, 'close' => $closeDate(1, 10), 'created' => 22, 'owner' => 1, 'path' => ['lead', 'qualified', 'proposal']],
    ['company' => '株式会社みどり物流', 'note' => '倉庫管理システム導入。要件定義込みで提案中、競合1社。', 'stage' => 'proposal', 'amount' => $yen(3200000), 'prob' => 50, 'close' => $closeDate(0, 22), 'created' => 28, 'owner' => 2, 'path' => ['lead', 'qualified', 'proposal']],
    ['company' => 'テクノ精機株式会社', 'note' => '検査装置の導入提案。デモ機貸出中、来月頭に評価結果が出る。', 'stage' => 'proposal', 'amount' => $yen(2300000), 'prob' => 60, 'close' => $closeDate(1, 25), 'created' => 25, 'owner' => 0, 'path' => ['lead', 'qualified', 'proposal']],
    // --- negotiation (2) --------------------------------------------------
    ['company' => '株式会社山川設備工業', 'note' => '空調設備更新工事一式。金額調整の最終段階、今月中の契約見込み。', 'stage' => 'negotiation', 'amount' => $yen(2850000), 'prob' => 75, 'close' => $closeDate(0, 18), 'created' => 38, 'owner' => 1, 'path' => ['lead', 'qualified', 'proposal', 'negotiation']],
    ['company' => '株式会社ワタナベ食品', 'note' => 'HACCP対応の設備改修。法務確認が済み次第、契約書締結へ。', 'stage' => 'negotiation', 'amount' => $yen(1980000), 'prob' => 70, 'close' => $closeDate(1, 5), 'created' => 32, 'owner' => 2, 'path' => ['lead', 'qualified', 'proposal', 'negotiation']],
    // --- won (2) ----------------------------------------------------------
    ['company' => '桜井建設株式会社', 'note' => '現場事務所のIT環境整備。受注済み、今週キックオフ。', 'stage' => 'won', 'amount' => $yen(750000), 'prob' => 100, 'close' => $closeDate(0, $landedDay(6)), 'created' => 42, 'owner' => 0, 'path' => ['lead', 'qualified', 'proposal', 'negotiation', 'won']],
    ['company' => '東都ビルメンテナンス株式会社', 'note' => '業務用清掃ロボット導入。受注済み、納品は月末予定。', 'stage' => 'won', 'amount' => $yen(1650000), 'prob' => 100, 'close' => $closeDate(0, $landedDay(11)), 'created' => 48, 'owner' => 1, 'path' => ['lead', 'qualified', 'proposal', 'negotiation', 'won']],
    // --- lost (1) ---------------------------------------------------------
    ['company' => '株式会社アオキ興産', 'note' => '社内ネットワーク刷新。予算凍結のため今期は見送りに。来期に再アプローチ。', 'stage' => 'lost', 'amount' => $yen(1200000), 'prob' => 0, 'close' => $closeDate(0, $landedDay(9)), 'created' => 45, 'owner' => 2, 'path' => ['lead', 'qualified', 'proposal', 'lost']],
];

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

    foreach ($deals as $i => $deal) {
        $dealId = (string) new Ulid();
        $ownerId = $userIds[$deal['owner']];
        $createdDaysAgo = $deal['created'];
        $path = $deal['path'];

        // Timeline: creation at `created` days ago, then one stage move per
        // remaining path step, spaced evenly towards the recent past. Hours
        // vary per deal so the timeline does not look machine-stamped.
        $moves = count($path) - 1;
        $span = max($createdDaysAgo - 2, 1); // last event ~2 days ago at the earliest
        $hour = 9 + ($i % 8);                // 9:00–16:00
        $minute = ($i * 17) % 60;

        $createdAt = $daysAgo($createdDaysAgo, $hour, $minute);
        $lastEventAt = $moves > 0 ? $daysAgo(max($createdDaysAgo - $span, 1), $hour, $minute) : $createdAt;

        // Deal row first (deal_stage_history.deal_id has an FK on deals.id).
        $insertDeal->execute([
            $dealId,
            $orgId,
            $deal['company'],
            $deal['amount'],
            $stageIdBySlug[$deal['stage']],
            $deal['prob'],
            $deal['close'],
            $ownerId,
            $deal['note'],
            $createdAt,
            $lastEventAt,
        ]);

        $insertActivity->execute([(string) new Ulid(), $dealId, null, $stageIdBySlug[$path[0]], 'created', $ownerId, $createdAt]);

        for ($step = 1; $step <= $moves; $step++) {
            $daysBack = $createdDaysAgo - intdiv($span * $step, $moves);
            $insertActivity->execute([
                (string) new Ulid(),
                $dealId,
                $stageIdBySlug[$path[$step - 1]],
                $stageIdBySlug[$path[$step]],
                'stage_changed',
                $ownerId,
                $daysAgo(max($daysBack, 1), $hour, $minute),
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
foreach (['lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost'] as $slug) {
    $parts[] = $slug . ' ' . ($stageCounts[$slug] ?? 0);
}
echo implode(' / ', $parts) . "\n\n";
echo "Demo credentials (shared password" . (getenv('NENE_DEAL_DEMO_PASSWORD') ? ' from NENE_DEAL_DEMO_PASSWORD' : ": \"{$demoPassword}\"") . "):\n";
foreach ($demoUsers as $user) {
    echo "  {$user['email']}  ({$user['role']})\n";
}
echo "\nReset: re-run this command any time — it wipes the organization's deals and reseeds.\n";
exit(0);
