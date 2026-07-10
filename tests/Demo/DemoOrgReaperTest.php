<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Demo;

use NeneDeal\Demo\DemoDataSeeder;
use NeneDeal\Demo\DemoOrgProvisioner;
use NeneDeal\Demo\DemoOrgReaper;
use NeneDeal\Demo\DemoTemplate;
use NeneDeal\Tests\Support\FixedClock;
use Symfony\Component\Uid\Ulid;

final class DemoOrgReaperTest extends DemoDatabaseTestCase
{
    public function test_reap_removes_the_demo_org_and_all_children_but_spares_other_orgs(): void
    {
        $clock = new FixedClock();

        // A non-demo organization that must survive untouched.
        $fixedOrgId = (string) new Ulid();
        $now = '2026-06-01 00:00:00';
        $this->query->execute(
            'INSERT INTO organizations (id, slug, name, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$fixedOrgId, 'default', 'Default Organization', $now, $now],
        );
        $this->query->execute(
            'INSERT INTO users (id, organization_id, email, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [(string) new Ulid(), $fixedOrgId, 'demo-admin@nene-deal.test', 'x', 'admin', $now, $now],
        );

        $provisioner = new DemoOrgProvisioner($this->query, $clock, $this->handles);
        $org = $provisioner->provision('demo-reapme', 'standard');
        (new DemoDataSeeder($this->query, $clock, $this->handles))->seed($org->orgId, DemoTemplate::Standard);

        $orgId = $this->handles->organizationId($org->orgId);
        self::assertSame(15, $this->countRows('SELECT COUNT(*) FROM deals WHERE organization_id = ?', [$orgId]));

        (new DemoOrgReaper($this->query, $this->handles))->reap($org->orgId);

        self::assertSame(0, $this->countRows('SELECT COUNT(*) FROM organizations WHERE id = ?', [$orgId]));
        self::assertSame(0, $this->countRows('SELECT COUNT(*) FROM deals WHERE organization_id = ?', [$orgId]));
        self::assertSame(0, $this->countRows('SELECT COUNT(*) FROM pipeline_stages WHERE organization_id = ?', [$orgId]));
        self::assertSame(0, $this->countRows('SELECT COUNT(*) FROM users WHERE organization_id = ?', [$orgId]));
        self::assertSame(0, $this->countRows('SELECT COUNT(*) FROM deal_stage_history'));

        // The fixed org is intact.
        self::assertSame(1, $this->countRows('SELECT COUNT(*) FROM organizations WHERE id = ?', [$fixedOrgId]));
        self::assertSame(1, $this->countRows('SELECT COUNT(*) FROM users WHERE organization_id = ?', [$fixedOrgId]));
    }

    public function test_reap_is_idempotent(): void
    {
        $clock = new FixedClock();
        $org = (new DemoOrgProvisioner($this->query, $clock, $this->handles))->provision('demo-twice', 'standard');

        $reaper = new DemoOrgReaper($this->query, $this->handles);
        $reaper->reap($org->orgId);
        $reaper->reap($org->orgId); // already gone — must be success, not an error

        self::assertSame(0, $this->countRows('SELECT COUNT(*) FROM organizations'));
    }
}
