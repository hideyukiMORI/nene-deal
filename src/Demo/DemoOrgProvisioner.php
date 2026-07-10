<?php

declare(strict_types=1);

namespace NeneDeal\Demo;

use Nene2\Database\DatabaseConstraintException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Demo\DisposableOrgProvisionerInterface;
use Nene2\Demo\ProvisionedDemoOrg;
use Nene2\Demo\SlugConflictException;
use Nene2\Http\ClockInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Creates one disposable demo organization (`Nene2\Demo` consumer, #69):
 * the org row, the default six-stage pipeline (same set the migration seeds
 * for a real install — {@see DemoPipelineFixture::STAGE_SLUGS}), and a
 * throwaway admin.
 *
 * The admin's password is random and never disclosed: the demo session is
 * seated by minting a bearer token directly ({@see DemoSessionSeater}), so
 * nobody can (or needs to) log in as a demo admin from the login form. Its
 * email is namespaced by the org slug (`users.email` is globally unique in
 * Deal's schema, so per-org fixed addresses would collide across demos).
 *
 * Writes go through the one injected query executor — the same connection the
 * request already uses (design-doc rule: a second PDO deadlocks under SQLite).
 */
final readonly class DemoOrgProvisioner implements DisposableOrgProvisionerInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private ClockInterface $clock,
        private DemoOrgHandles $handles,
    ) {
    }

    public function provision(string $slug, string $template): ProvisionedDemoOrg
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        $orgId = (string) new Ulid();

        try {
            $this->query->execute(
                'INSERT INTO organizations (id, slug, name, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                [$orgId, $slug, 'デモ商事株式会社（お試し）', $now, $now],
            );
        } catch (DatabaseConstraintException $exception) {
            throw new SlugConflictException(sprintf('Organization slug "%s" is already taken.', $slug), previous: $exception);
        }

        // slug, label, sort_order, is_terminal, is_won — identical to the
        // default set the install migration seeds.
        $stages = [
            ['lead', 'Lead', 1, 0, 0],
            ['qualified', 'Qualified', 2, 0, 0],
            ['proposal', 'Proposal', 3, 0, 0],
            ['negotiation', 'Negotiation', 4, 0, 0],
            ['won', 'Won', 5, 1, 1],
            ['lost', 'Lost', 6, 1, 0],
        ];

        foreach ($stages as [$stageSlug, $label, $order, $terminal, $won]) {
            $this->query->execute(
                'INSERT INTO pipeline_stages (id, organization_id, slug, label, sort_order, is_terminal, is_won, created_at, updated_at)'
                . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [(string) new Ulid(), $orgId, $stageSlug, $label, $order, $terminal, $won, $now, $now],
            );
        }

        $adminId = (string) new Ulid();
        $this->query->execute(
            'INSERT INTO users (id, organization_id, email, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $adminId,
                $orgId,
                sprintf('demo-admin@%s.nene-deal.test', $slug),
                password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                'admin',
                $now,
                $now,
            ],
        );

        $handle = $this->handles->register($orgId, $adminId, $slug);

        return new ProvisionedDemoOrg(orgId: $handle, slug: $slug, adminUserId: $handle);
    }
}
