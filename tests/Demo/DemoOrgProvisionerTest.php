<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Demo;

use Nene2\Demo\SlugConflictException;
use NeneDeal\Demo\DemoOrgProvisioner;
use NeneDeal\Demo\DemoPipelineFixture;
use NeneDeal\Tests\Support\FixedClock;

final class DemoOrgProvisionerTest extends DemoDatabaseTestCase
{
    private function provisioner(): DemoOrgProvisioner
    {
        return new DemoOrgProvisioner($this->query, new FixedClock(), $this->handles);
    }

    public function test_provisions_org_with_default_stages_and_admin(): void
    {
        $org = $this->provisioner()->provision('demo-abc123', 'standard');

        self::assertSame('demo-abc123', $org->slug);

        $orgId = $this->handles->organizationId($org->orgId);
        $row = $this->query->fetchOne('SELECT slug, name FROM organizations WHERE id = ?', [$orgId]);
        self::assertIsArray($row);
        self::assertSame('demo-abc123', $row['slug']);

        $stages = $this->query->fetchAll(
            'SELECT slug FROM pipeline_stages WHERE organization_id = ? ORDER BY sort_order',
            [$orgId],
        );
        self::assertSame(DemoPipelineFixture::STAGE_SLUGS, array_map(static fn (array $s): string => (string) $s['slug'], $stages));

        $adminId = $this->handles->adminUserId($org->adminUserId);
        $admin = $this->query->fetchOne('SELECT organization_id, email, role FROM users WHERE id = ?', [$adminId]);
        self::assertIsArray($admin);
        self::assertSame($orgId, $admin['organization_id']);
        self::assertSame('admin', $admin['role']);
        self::assertSame('demo-admin@demo-abc123.nene-deal.test', $admin['email']);
    }

    public function test_slug_conflict_throws_the_framework_exception(): void
    {
        $provisioner = $this->provisioner();
        $provisioner->provision('demo-abc123', 'standard');

        $this->expectException(SlugConflictException::class);
        $provisioner->provision('demo-abc123', 'standard');
    }
}
