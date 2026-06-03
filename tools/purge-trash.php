<?php

declare(strict_types=1);

/**
 * Recycle-bin purge: permanently removes deals that were soft-deleted more than
 * the retention window ago. Run from cron (e.g. daily). Until purged, deleted
 * deals stay recoverable via POST /deals/{id}/restore and the board's
 * "show deleted" toggle.
 *
 * Permanent removal also drops that deal's activity rows (FK ON DELETE CASCADE),
 * which is the intended meaning of a permanent delete — nothing of the purged
 * deal remains. The audit trail of *surviving* deals is untouched.
 *
 * Retention is read from NENE_DEAL_TRASH_RETENTION_DAYS (default 30).
 * Pass --dry-run to report the count without deleting.
 *
 * Usage:
 *   php tools/purge-trash.php [--dry-run]
 */

use Nene2\Config\ConfigLoader;
use Nene2\Database\PdoConnectionFactory;

require_once __DIR__ . '/../vendor/autoload.php';

$dryRun = in_array('--dry-run', $argv, true);

$retentionDays = (int) (getenv('NENE_DEAL_TRASH_RETENTION_DAYS') ?: '30');
if ($retentionDays < 1) {
    fwrite(STDERR, "NENE_DEAL_TRASH_RETENTION_DAYS must be >= 1.\n");
    exit(1);
}

$cutoff = (new DateTimeImmutable("-{$retentionDays} days"))->format('Y-m-d H:i:s');

$database = (new ConfigLoader(__DIR__ . '/..'))->load()->database;
$pdo = (new PdoConnectionFactory($database))->create();

$count = $pdo->prepare('SELECT COUNT(*) FROM deals WHERE deleted_at IS NOT NULL AND deleted_at < ?');
$count->execute([$cutoff]);
$total = (int) $count->fetchColumn();

if ($dryRun) {
    echo "[dry-run] {$total} deal(s) deleted before {$cutoff} would be purged.\n";
    exit(0);
}

if ($total === 0) {
    echo "Nothing to purge (retention {$retentionDays}d, cutoff {$cutoff}).\n";
    exit(0);
}

$delete = $pdo->prepare('DELETE FROM deals WHERE deleted_at IS NOT NULL AND deleted_at < ?');
$delete->execute([$cutoff]);

echo "Purged {$total} deal(s) deleted before {$cutoff} (retention {$retentionDays}d).\n";
exit(0);
