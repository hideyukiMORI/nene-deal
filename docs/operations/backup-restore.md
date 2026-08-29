# Production database backup and restore (HETEML)

How the production database of NeNe Deal is backed up, how to tell that a backup
actually ran, how to check that a dump is worth restoring, and how to restore it.

Written for `#223`. Every figure below is measured; the date of the measurement is
attached, so a stale line stays honest rather than becoming a lie.

## 1. The device

| | |
|---|---|
| Script | `~/bin/nene-db-backup.sh <product>` (`contact` / `deal` / `clear` / `invoice` / `vault`) |
| Wrapper actually scheduled | `~/bin/nene-db-backup-all.sh` — runs all five products in order, one product's failure does not stop the rest, returns `rc=1` if any failed |
| Schedule | **one** control-panel cron entry, `45 4 * * * bin/nene-db-backup-all.sh` (registered 2026-08-27; the panel's slots are scarce, so five products share one entry) |
| Dumps | `~/backups/deal-db/daily/deal-<YYYYMMDD>.sql.gz`, and `~/backups/deal-db/weekly/` |
| Log | `~/site-logs/nene-db-backup-deal-runs.log` |
| Retention | daily **14** generations, weekly **8** |
| Credentials | read at run time from the product's `.env` (`DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASSWORD`); never passed on the command line |

`mysqldump` runs with `--single-transaction --routines --triggers --no-tablespaces`.
`--no-tablespaces` is required: shared hosting gives the DB user no `PROCESS` privilege.

**A weekly copy is made only on Sundays** — the script guards it with `[ "$(date +%u)" = "7" ]`.
So `weekly=0` in the log is normal until the first Sunday after the cron was registered; it is
evidence of nothing before then. A periodic job is not observed until its period has come round
once.

## 2. How to tell that a backup ran

🔴 **Read the log. Do not read the file list.**

The daily file is named after the date and is *overwritten in place*, so a hand-taken dump and the
cron's own dump land on the same path. The existence of `deal-20260828.sql.gz` therefore does not
mean the cron ran that morning — on 2026-08-28 the cron produced it at 04:45 (8,839 B) and a
pre-deploy manual dump overwrote it at 18:12 (8,838 B). The file list shows one dump; the log shows
two runs.

```bash
tail -3 ~/site-logs/nene-db-backup-deal-runs.log
```

```
2026-08-28T04:45:02+09:00 rc=0 gap_since_ok=6.1h  result=OK bytes=8839 daily=3 weekly=0
2026-08-28T18:12:52+09:00 rc=0 gap_since_ok=13.5h result=OK bytes=8838 daily=3 weekly=0
2026-08-29T04:45:01+09:00 rc=0 gap_since_ok=10.5h result=OK bytes=10407 daily=4 weekly=0
```

`gap_since_ok` is the distance from the previous successful run. Anything much larger than 24h
means a run was missed. Cron mail is not a substitute — HETEML's cron mail delivery has been
unreliable (measured 2026-07-28).

## 3. Checking a dump before you trust it

Size is a floor, not a guarantee. The script's `MIN_BYTES` is 4 KB and a schema-only dump passes
it — this is not hypothetical: a sibling backup on the same host was once producing dumps with 13
`CREATE TABLE` statements and 2 `INSERT`s, i.e. no data at all, while reporting success.

```bash
gunzip -t ~/backups/deal-db/daily/deal-<YYYYMMDD>.sql.gz && echo 'gzip ok'
gunzip -c ~/backups/deal-db/daily/deal-<YYYYMMDD>.sql.gz > ~/dump-check.sql

grep -c 'CREATE TABLE' ~/dump-check.sql          # expect 8
grep -c 'INSERT INTO `deals`' ~/dump-check.sql   # expect >= 1   (the point of the check)
grep -c 'INSERT INTO `no_such_table`' ~/dump-check.sql   # negative control: expect 0
tail -5 ~/dump-check.sql | grep -- '-- Dump completed'   # truncation guard
rm -f ~/dump-check.sql
```

Ask for one table you expect to be populated **and** one that cannot exist. A grep that is silently
broken returns `0` for both; only the negative control separates "no rows" from "my pattern never
matches anything".

Measured on `deal-20260829.sql.gz` (2026-08-29 18:20 JST): 65,091 B uncompressed, 8 tables, and
`INSERT` rows present for 7 of them — `deals` ~30, `deal_stage_history` ~82, `audit_events` ~81,
`pipeline_stages` 12, `phinxlog` 14, `users` 7, `organizations` 2. `login_attempts` is empty, which
is expected: it holds only recent failed-login records.

The script also carries its own two-sided self-test — it must accept the latest good dump and
reject a truncated copy of it:

```bash
~/bin/nene-db-backup.sh deal --selftest
```

## 4. Restoring

**Preconditions.** Take a dump of the current state first, even when it is believed to be broken —
you cannot compare against a state you did not keep. Put the credentials in a `0600` defaults file
rather than on the command line, so they appear in neither `ps` nor the shell history:

```bash
umask 077
cat > ~/.deal-restore.cnf <<'EOF'
[client]
host=<DB_HOST from the product's .env>
user=<DB_USER>
password=<DB_PASSWORD>
EOF
```

**Order matters.** If code and database moved together (a migration), roll the *code* back first and
the database second. Restoring the database under the new code leaves a window in which the
application reads old data with new semantics — for the `amount_cents` migration that window
displays every amount off by two orders of magnitude.

```bash
# 1. code first, if it moved
mv ~/web/domain/ayane_co_jp/deal ~/deal-broken && mv ~/deal-old-<YYYYMMDD> ~/web/domain/ayane_co_jp/deal

# 2. then the database
gunzip -c ~/backups/deal-db/daily/deal-<YYYYMMDD>.sql.gz \
  | mysql --defaults-extra-file=~/.deal-restore.cnf <DB_NAME>

rm -f ~/.deal-restore.cnf
```

**After restoring, verify — do not assume.** A restore that half-applied still exits 0 on the
last statement:

```bash
mysql --defaults-extra-file=~/.deal-restore.cnf <DB_NAME> -e '
  SELECT COUNT(*) AS deals, MAX(amount_cents) AS max_amount FROM deals;
  SELECT COUNT(*) AS migrations FROM phinxlog;'
```

Compare the counts against the dump's own `INSERT` census from §3, then open the board in a browser
and read an amount. Numbers agreeing with numbers is not the same as the screen being right.

## 5. What this box does not have

Measured 2026-08-29 18:21 JST, with `definitely_not_a_tool` as a negative control:

- **Present**: `mysql`, `mysqldump`, `gzip`, `gunzip`, `php8.4`
- **Absent**: `sha256sum`, `openssl`, `shasum`, `df`, `crontab`, `mktemp`

Consequences:

- Hash with `php8.4 -r 'echo hash_file("sha256", $argv[1]);' <file>`.
- Cron is registered through the control panel — **`crontab -l` returning nothing is not evidence
  that no cron exists**, and adding an entry is the owner's action, not something a script can do.
- Scripts that reach for `mktemp` fail on this box; use an explicit path under `$HOME`.

On a restricted box an empty result is not a finding until a positive control shows the tool ran
at all.

Last updated: 2026-08-29
