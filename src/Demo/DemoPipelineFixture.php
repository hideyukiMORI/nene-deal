<?php

declare(strict_types=1);

namespace NeneDeal\Demo;

use DateTimeImmutable;

/**
 * The single source of Deal's demo pipeline dataset: a funnel-shaped spread of
 * 15 deals (lead-heavy → few terminal) with realistic Japanese company names,
 * amounts in the hundreds-of-thousands to millions JPY range, and per-deal
 * stage timelines.
 *
 * Shared verbatim by the two demo forms so they never drift:
 *
 * - `tools/seed-demo.php` — the fixed demo org (guided demos with handed-out
 *   credentials, reset by re-running the seed / its cron), and
 * - {@see DemoDataSeeder} — the disposable per-visitor orgs of the
 *   `/demo/{template}` route (#69).
 *
 * All dates are computed relative to the passed "today" so the board always
 * looks like a live pipeline: won/lost deals landed earlier this month, open
 * deals close this month / next month / the month after. Timeline events are
 * spaced from the deal's creation towards the recent past with per-deal hour
 * and minute jitter so nothing looks machine-stamped.
 */
final readonly class DemoPipelineFixture
{
    /** The default stage set every organization is provisioned with. */
    public const array STAGE_SLUGS = ['lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];

    /**
     * Owner rota: index into the three demo people (0 = admin, 1–2 = operators).
     */
    public const int OWNER_COUNT = 3;

    /**
     * The dataset with every timestamp resolved.
     *
     * @return list<array{
     *   company: string,
     *   note: string,
     *   stage: string,
     *   amount_cents: int,
     *   probability: int,
     *   close_date: string,
     *   owner: int,
     *   created_at: string,
     *   updated_at: string,
     *   timeline: list<array{from: ?string, to: string, action: string, created_at: string}>
     * }>
     */
    public static function deals(DateTimeImmutable $today): array
    {
        $today = $today->setTime(0, 0);

        /** Timestamp string N days before today, at a plausible working hour. */
        $daysAgo = static function (int $days, int $hour, int $minute) use ($today): string {
            return $today->modify("-{$days} days")->setTime($hour, $minute)->format('Y-m-d H:i:s');
        };

        /**
         * A close date pinned to a day (1–28) of the month `monthOffset` months
         * from the current one, so the forecast month buckets are stable no
         * matter when the seed runs.
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

        $yen = static fn (int $yenAmount): int => $yenAmount * 100; // integer JPY minor units

        $specs = [
            // --- lead (4) -------------------------------------------------
            ['company' => '有限会社高橋電設', 'note' => '事務所・倉庫の照明LED化改修。紹介案件、来週初回訪問予定。', 'stage' => 'lead', 'amount' => $yen(620000), 'prob' => 10, 'close' => $closeDate(1, 20), 'created' => 4, 'owner' => 1, 'path' => ['lead']],
            ['company' => '株式会社北斗商事', 'note' => '基幹システム更改の情報収集段階。予算感のヒアリングから。', 'stage' => 'lead', 'amount' => $yen(5400000), 'prob' => 10, 'close' => $closeDate(2, 25), 'created' => 6, 'owner' => 2, 'path' => ['lead']],
            ['company' => '株式会社フジイ塗装', 'note' => '見積管理ツールの引き合い。展示会で名刺交換、資料送付済み。', 'stage' => 'lead', 'amount' => $yen(350000), 'prob' => 20, 'close' => $closeDate(2, 10), 'created' => 8, 'owner' => 1, 'path' => ['lead']],
            ['company' => '株式会社カネコ金属', 'note' => '工場換気設備の更新。現場下見の日程調整中。', 'stage' => 'lead', 'amount' => $yen(540000), 'prob' => 20, 'close' => $closeDate(0, 26), 'created' => 3, 'owner' => 0, 'path' => ['lead']],
            // --- qualified (3) --------------------------------------------
            ['company' => '株式会社青葉製作所', 'note' => '生産ライン保守契約（年間）。決裁者と面談済み、要件整理中。', 'stage' => 'qualified', 'amount' => $yen(1440000), 'prob' => 35, 'close' => $closeDate(1, 15), 'created' => 14, 'owner' => 2, 'path' => ['lead', 'qualified']],
            ['company' => '中央メディカルサービス株式会社', 'note' => '予約システム保守の乗り換え検討。現行契約は再来月末まで。', 'stage' => 'qualified', 'amount' => $yen(864000), 'prob' => 35, 'close' => $closeDate(0, 24), 'created' => 12, 'owner' => 1, 'path' => ['lead', 'qualified']],
            ['company' => '湘南ハウジング株式会社', 'note' => 'モデルハウスのスマートホーム対応。予算枠は確認済み。', 'stage' => 'qualified', 'amount' => $yen(980000), 'prob' => 40, 'close' => $closeDate(2, 15), 'created' => 18, 'owner' => 0, 'path' => ['lead', 'qualified']],
            // --- proposal (3) ---------------------------------------------
            ['company' => '大和田印刷株式会社', 'note' => '会社案内・営業ツール一式のリニューアル。提案書提出済み、反応良好。', 'stage' => 'proposal', 'amount' => $yen(480000), 'prob' => 55, 'close' => $closeDate(1, 10), 'created' => 22, 'owner' => 1, 'path' => ['lead', 'qualified', 'proposal']],
            ['company' => '株式会社みどり物流', 'note' => '倉庫管理システム導入。要件定義込みで提案中、競合1社。', 'stage' => 'proposal', 'amount' => $yen(3200000), 'prob' => 50, 'close' => $closeDate(0, 22), 'created' => 28, 'owner' => 2, 'path' => ['lead', 'qualified', 'proposal']],
            ['company' => 'テクノ精機株式会社', 'note' => '検査装置の導入提案。デモ機貸出中、来月頭に評価結果が出る。', 'stage' => 'proposal', 'amount' => $yen(2300000), 'prob' => 60, 'close' => $closeDate(1, 25), 'created' => 25, 'owner' => 0, 'path' => ['lead', 'qualified', 'proposal']],
            // --- negotiation (2) ------------------------------------------
            ['company' => '株式会社山川設備工業', 'note' => '空調設備更新工事一式。金額調整の最終段階、今月中の契約見込み。', 'stage' => 'negotiation', 'amount' => $yen(2850000), 'prob' => 75, 'close' => $closeDate(0, 18), 'created' => 38, 'owner' => 1, 'path' => ['lead', 'qualified', 'proposal', 'negotiation']],
            ['company' => '株式会社ワタナベ食品', 'note' => 'HACCP対応の設備改修。法務確認が済み次第、契約書締結へ。', 'stage' => 'negotiation', 'amount' => $yen(1980000), 'prob' => 70, 'close' => $closeDate(1, 5), 'created' => 32, 'owner' => 2, 'path' => ['lead', 'qualified', 'proposal', 'negotiation']],
            // --- won (2) --------------------------------------------------
            ['company' => '桜井建設株式会社', 'note' => '現場事務所のIT環境整備。受注済み、今週キックオフ。', 'stage' => 'won', 'amount' => $yen(750000), 'prob' => 100, 'close' => $closeDate(0, $landedDay(6)), 'created' => 42, 'owner' => 0, 'path' => ['lead', 'qualified', 'proposal', 'negotiation', 'won']],
            ['company' => '東都ビルメンテナンス株式会社', 'note' => '業務用清掃ロボット導入。受注済み、納品は月末予定。', 'stage' => 'won', 'amount' => $yen(1650000), 'prob' => 100, 'close' => $closeDate(0, $landedDay(11)), 'created' => 48, 'owner' => 1, 'path' => ['lead', 'qualified', 'proposal', 'negotiation', 'won']],
            // --- lost (1) -------------------------------------------------
            ['company' => '株式会社アオキ興産', 'note' => '社内ネットワーク刷新。予算凍結のため今期は見送りに。来期に再アプローチ。', 'stage' => 'lost', 'amount' => $yen(1200000), 'prob' => 0, 'close' => $closeDate(0, $landedDay(9)), 'created' => 45, 'owner' => 2, 'path' => ['lead', 'qualified', 'proposal', 'lost']],
        ];

        $deals = [];

        foreach ($specs as $i => $spec) {
            $createdDaysAgo = $spec['created'];
            $path = $spec['path'];

            // Timeline: creation at `created` days ago, then one stage move per
            // remaining path step, spaced evenly towards the recent past. Hours
            // vary per deal so the timeline does not look machine-stamped.
            $moves = count($path) - 1;
            $span = max($createdDaysAgo - 2, 1); // last event ~2 days ago at the earliest
            $hour = 9 + ($i % 8);                // 9:00–16:00
            $minute = ($i * 17) % 60;

            $createdAt = $daysAgo($createdDaysAgo, $hour, $minute);
            $lastEventAt = $moves > 0 ? $daysAgo(max($createdDaysAgo - $span, 1), $hour, $minute) : $createdAt;

            $timeline = [
                ['from' => null, 'to' => $path[0], 'action' => 'created', 'created_at' => $createdAt],
            ];

            $previous = $path[0];
            $step = 0;
            foreach (array_slice($path, 1) as $next) {
                $step++;
                $daysBack = $createdDaysAgo - intdiv($span * $step, $moves);
                $timeline[] = [
                    'from' => $previous,
                    'to' => $next,
                    'action' => 'stage_changed',
                    'created_at' => $daysAgo(max($daysBack, 1), $hour, $minute),
                ];
                $previous = $next;
            }

            $deals[] = [
                'company' => $spec['company'],
                'note' => $spec['note'],
                'stage' => $spec['stage'],
                'amount_cents' => $spec['amount'],
                'probability' => $spec['prob'],
                'close_date' => $spec['close'],
                'owner' => $spec['owner'],
                'created_at' => $createdAt,
                'updated_at' => $lastEventAt,
                'timeline' => $timeline,
            ];
        }

        return $deals;
    }
}
