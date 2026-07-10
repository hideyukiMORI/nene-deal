<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Demo;

use NeneDeal\Demo\DemoDataSeeder;
use NeneDeal\Demo\DemoOrgProvisioner;
use NeneDeal\Demo\DemoTemplate;
use NeneDeal\Tests\Support\FixedClock;

final class DemoDataSeederTest extends DemoDatabaseTestCase
{
    public function test_seeds_the_funnel_shaped_pipeline_into_the_new_org(): void
    {
        $clock = new FixedClock();
        $org = (new DemoOrgProvisioner($this->query, $clock, $this->handles))->provision('demo-seedme', 'standard');

        (new DemoDataSeeder($this->query, $clock, $this->handles))->seed($org->orgId, DemoTemplate::Standard);

        $orgId = $this->handles->organizationId($org->orgId);

        self::assertSame(15, $this->countRows('SELECT COUNT(*) FROM deals WHERE organization_id = ?', [$orgId]));

        // Funnel shape: lead 4 / qualified 3 / proposal 3 / negotiation 2 / won 2 / lost 1.
        $byStage = [];
        $rows = $this->query->fetchAll(
            'SELECT s.slug AS slug, COUNT(*) AS n FROM deals d JOIN pipeline_stages s ON s.id = d.stage_id'
            . ' WHERE d.organization_id = ? GROUP BY s.slug',
            [$orgId],
        );
        foreach ($rows as $row) {
            $byStage[(string) $row['slug']] = (int) $row['n'];
        }
        self::assertSame(
            ['lead' => 4, 'qualified' => 3, 'proposal' => 3, 'negotiation' => 2, 'won' => 2, 'lost' => 1],
            ['lead' => $byStage['lead'], 'qualified' => $byStage['qualified'], 'proposal' => $byStage['proposal'], 'negotiation' => $byStage['negotiation'], 'won' => $byStage['won'], 'lost' => $byStage['lost']],
        );

        // Owner spread: the admin plus the two seeded operators.
        self::assertSame(3, $this->countRows('SELECT COUNT(*) FROM users WHERE organization_id = ?', [$orgId]));
        self::assertSame(2, $this->countRows("SELECT COUNT(*) FROM users WHERE organization_id = ? AND role = 'operator'", [$orgId]));
        self::assertSame(
            0,
            $this->countRows('SELECT COUNT(*) FROM deals WHERE organization_id = ? AND owner_user_id IS NULL', [$orgId]),
        );

        // Every deal has a creation event; multi-step paths add stage moves.
        self::assertSame(
            15,
            $this->countRows(
                "SELECT COUNT(*) FROM deal_stage_history WHERE action = 'created'"
                . ' AND deal_id IN (SELECT id FROM deals WHERE organization_id = ?)',
                [$orgId],
            ),
        );
        self::assertSame(
            26,
            $this->countRows(
                "SELECT COUNT(*) FROM deal_stage_history WHERE action = 'stage_changed'"
                . ' AND deal_id IN (SELECT id FROM deals WHERE organization_id = ?)',
                [$orgId],
            ),
        );
    }

    public function test_operator_emails_are_slug_namespaced_so_demos_never_collide(): void
    {
        $clock = new FixedClock();
        $provisioner = new DemoOrgProvisioner($this->query, $clock, $this->handles);
        $seeder = new DemoDataSeeder($this->query, $clock, $this->handles);

        $first = $provisioner->provision('demo-one', 'standard');
        $seeder->seed($first->orgId, DemoTemplate::Standard);

        // A second demo org must seed cleanly despite the global unique email index.
        $second = $provisioner->provision('demo-two', 'standard');
        $seeder->seed($second->orgId, DemoTemplate::Standard);

        self::assertSame(
            15,
            $this->countRows('SELECT COUNT(*) FROM deals WHERE organization_id = ?', [$this->handles->organizationId($second->orgId)]),
        );
    }
}
