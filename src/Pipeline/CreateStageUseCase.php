<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use Nene2\Audit\AuditEvent;
use Nene2\Audit\AuditRecorderInterface;
use Nene2\Http\ClockInterface;
use NeneDeal\Audit\AuditAction;
use NeneDeal\Tenancy\CurrentOrganization;
use Symfony\Component\Uid\Ulid;

final readonly class CreateStageUseCase
{
    public function __construct(
        private PipelineStageRepositoryInterface $stages,
        private CurrentOrganization $organization,
        private ClockInterface $clock,
        private AuditRecorderInterface $audit,
    ) {
    }

    /** @throws SlugAlreadyTakenException */
    public function execute(CreateStageInput $input, ?string $actorUserId = null): PipelineStage
    {
        $slug = $this->deriveSlug($input->label);

        if ($this->stages->slugExists($slug)) {
            throw new SlugAlreadyTakenException($slug);
        }

        $now = $this->clock->now()->format('Y-m-d H:i:s');
        $stage = new PipelineStage(
            id: (string) new Ulid(),
            organizationId: $this->organization->id(),
            slug: $slug,
            label: $input->label,
            sortOrder: $input->sortOrder,
            isTerminal: false,
            isWon: false,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->stages->save($stage);

        $this->audit->record(new AuditEvent(
            action: AuditAction::STAGE_CREATED,
            entityType: 'stage',
            entityId: $stage->id,
            actorId: $actorUserId,
            organizationId: $this->organization->id(),
            after: ['slug' => $stage->slug, 'label' => $stage->label, 'sort_order' => $stage->sortOrder],
        ));

        return $stage;
    }

    private function deriveSlug(string $label): string
    {
        $slug = strtolower(trim($label));
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug) ?? $slug;
        return trim($slug, '-');
    }
}
