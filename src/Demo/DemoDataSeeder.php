<?php

declare(strict_types=1);

namespace NeneDeal\Demo;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Demo\DemoDataSeederInterface;
use Nene2\Demo\DemoTemplateKeyInterface;
use Nene2\Http\ClockInterface;
use RuntimeException;
use Symfony\Component\Uid\Ulid;

/**
 * Seeds one freshly provisioned disposable demo org (`Nene2\Demo` consumer,
 * #69) with the same presentation-ready pipeline the fixed demo org gets from
 * `tools/seed-demo.php`: 15 funnel-shaped deals across the six stages, a
 * substantial monthly forecast, and per-deal stage timelines — the shared
 * dataset lives in {@see DemoPipelineFixture}.
 *
 * Two more operator users are created here so the board shows a realistic
 * owner spread (fixture owner indexes: 0 = the provisioned admin, 1–2 = the
 * operators). Their emails are slug-namespaced (`users.email` is globally
 * unique) and their passwords random and undisclosed — the only way into a
 * disposable demo is the seat page.
 *
 * Design-doc rules honoured: every row carries the explicit org id (the demo
 * route is org-less at entry — never a request-scoped tenant holder), all
 * writes go through the one injected executor (a second PDO deadlocks under
 * SQLite), and dates anchor to the injected clock so the board looks current.
 */
final readonly class DemoDataSeeder implements DemoDataSeederInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private ClockInterface $clock,
        private DemoOrgHandles $handles,
    ) {
    }

    public function seed(int $orgId, DemoTemplateKeyInterface $template): void
    {
        $organizationId = $this->handles->organizationId($orgId);
        $slug = $this->handles->slug($orgId);
        $today = $this->clock->now()->setTime(0, 0);
        $now = $today->setTime(9, 0)->format('Y-m-d H:i:s');

        // Owner rota (fixture indexes 0–2): the provisioned admin plus two
        // operators created here, same personas as the fixed demo org.
        $ownerIds = [$this->handles->adminUserId($orgId)];

        foreach (['sato', 'takahashi'] as $name) {
            $operatorId = (string) new Ulid();
            $this->query->execute(
                'INSERT INTO users (id, organization_id, email, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $operatorId,
                    $organizationId,
                    sprintf('%s@%s.nene-deal.test', $name, $slug),
                    password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                    'operator',
                    $now,
                    $now,
                ],
            );
            $ownerIds[] = $operatorId;
        }

        $stageIdBySlug = [];
        foreach ($this->query->fetchAll('SELECT slug, id FROM pipeline_stages WHERE organization_id = ?', [$organizationId]) as $row) {
            $stageIdBySlug[(string) $row['slug']] = (string) $row['id'];
        }

        foreach (DemoPipelineFixture::STAGE_SLUGS as $required) {
            if (!isset($stageIdBySlug[$required])) {
                throw new RuntimeException(sprintf('Provisioned demo org "%s" is missing the default stage "%s".', $slug, $required));
            }
        }

        foreach (DemoPipelineFixture::deals($today) as $deal) {
            $dealId = (string) new Ulid();
            $ownerId = $ownerIds[$deal['owner']];

            $this->query->execute(
                'INSERT INTO deals (id, organization_id, account_label, amount_cents, stage_id, probability_percent,'
                . ' expected_close_date, owner_user_id, note, created_at, updated_at)'
                . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $dealId,
                    $organizationId,
                    $deal['company'],
                    $deal['amount_cents'],
                    $stageIdBySlug[$deal['stage']],
                    $deal['probability'],
                    $deal['close_date'],
                    $ownerId,
                    $deal['note'],
                    $deal['created_at'],
                    $deal['updated_at'],
                ],
            );

            foreach ($deal['timeline'] as $event) {
                $this->query->execute(
                    'INSERT INTO deal_stage_history (id, deal_id, from_stage_id, to_stage_id, action, changes, actor_user_id, created_at)'
                    . ' VALUES (?, ?, ?, ?, ?, NULL, ?, ?)',
                    [
                        (string) new Ulid(),
                        $dealId,
                        $event['from'] !== null ? $stageIdBySlug[$event['from']] : null,
                        $stageIdBySlug[$event['to']],
                        $event['action'],
                        $ownerId,
                        $event['created_at'],
                    ],
                );
            }
        }
    }
}
