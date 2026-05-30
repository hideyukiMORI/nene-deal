<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

/**
 * Persistence for pipeline stages. Every query is scoped to the current
 * organization ({@see \NeneDeal\Tenancy\CurrentOrganization}); callers never
 * pass an organization id.
 */
interface PipelineStageRepositoryInterface
{
    /** @return list<PipelineStage> Ordered by sort_order ascending. */
    public function findAll(): array;

    /** Resolves a stage by ULID or slug within the organization. */
    public function findByIdOrSlug(string $idOrSlug): ?PipelineStage;
}
