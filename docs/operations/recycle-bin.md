# Recycle bin & data retention

Deleting a deal is **soft**: it sets `deals.deleted_at` and records a `deleted`
entry in the activity log. The deal disappears from the board, list and
forecast but stays **recoverable**.

## Recovering a deal

- **UI**: on the board, tick **"削除済みも表示 / Show deleted"**, then press
  **復元 / Restore** on the card.
- **API**: `POST /api/v1/deals/{dealId}/restore`.

## Automatic purge (bounding long-term growth)

Soft-deleted deals are kept for a retention window, then **permanently removed**
by a cron job. Permanent removal also drops that deal's activity rows
(`deal_stage_history` FK `ON DELETE CASCADE`) — nothing of a permanently deleted
deal remains. The activity trail of **surviving** deals is never touched, and it
is queried per-deal, so it stays fast regardless of total history size.

### Configuration

| Setting | Default | Where |
| --- | --- | --- |
| Retention window (days) | `30` | `NENE_DEAL_TRASH_RETENTION_DAYS` (see `compose.yaml`) |

If you change it on a running stack, recreate the app container
(`docker compose up -d`) so the new value is picked up.

### Running the purge

```sh
# Report what would be purged (no changes):
php tools/purge-trash.php --dry-run

# Actually purge deals soft-deleted more than the retention window ago:
php tools/purge-trash.php
```

Schedule it daily from cron, e.g. (host crontab):

```cron
# 03:10 every day — purge expired trash inside the app container
10 3 * * * docker compose -f /path/to/nene-deal/compose.yaml exec -T app php tools/purge-trash.php >> /var/log/nene-deal-purge.log 2>&1
```

## Why this design

The normal board / list / forecast always exclude soft-deleted deals
(`deleted_at IS NULL`, indexed), so day-to-day use never slows down or fills up.
Only the "show deleted" view and the raw row count grow with deletions, and the
purge bounds both to roughly one retention window — while the recoverability
window protects against accidental deletes.
