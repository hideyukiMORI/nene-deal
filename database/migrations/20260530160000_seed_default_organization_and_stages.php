<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Symfony\Component\Uid\Ulid;

/**
 * Seeds the single default organization and its default pipeline stages
 * (terminology.md: lead, qualified, proposal, negotiation, won, lost).
 *
 * MVP runs single-organization (tenant resolution is a later issue), so a sole
 * organization row is created here and resolved at runtime by CurrentOrganization.
 */
final class SeedDefaultOrganizationAndStages extends AbstractMigration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $orgId = (string) new Ulid();

        $this->table('organizations')->insert([
            'id' => $orgId,
            'slug' => 'default',
            'name' => 'Default Organization',
            'created_at' => $now,
            'updated_at' => $now,
        ])->saveData();

        // slug, label, sort_order, is_terminal, is_won
        $stages = [
            ['lead', 'Lead', 1, 0, 0],
            ['qualified', 'Qualified', 2, 0, 0],
            ['proposal', 'Proposal', 3, 0, 0],
            ['negotiation', 'Negotiation', 4, 0, 0],
            ['won', 'Won', 5, 1, 1],
            ['lost', 'Lost', 6, 1, 0],
        ];

        $rows = [];
        foreach ($stages as [$slug, $label, $order, $terminal, $won]) {
            $rows[] = [
                'id' => (string) new Ulid(),
                'organization_id' => $orgId,
                'slug' => $slug,
                'label' => $label,
                'sort_order' => $order,
                'is_terminal' => $terminal,
                'is_won' => $won,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->table('pipeline_stages')->insert($rows)->saveData();
    }

    public function down(): void
    {
        $this->execute("DELETE FROM pipeline_stages WHERE organization_id IN (SELECT id FROM organizations WHERE slug = 'default')");
        $this->execute("DELETE FROM organizations WHERE slug = 'default'");
    }
}
