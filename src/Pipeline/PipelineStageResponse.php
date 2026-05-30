<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

/**
 * Serializes a {@see PipelineStage} to its snake_case JSON representation
 * (see the PipelineStage schema in docs/openapi/openapi.yaml).
 */
final class PipelineStageResponse
{
    /** @return array<string, mixed> */
    public static function toArray(PipelineStage $stage): array
    {
        return [
            'id' => $stage->id,
            'organization_id' => $stage->organizationId,
            'slug' => $stage->slug,
            'label' => $stage->label,
            'sort_order' => $stage->sortOrder,
            'is_terminal' => $stage->isTerminal,
            'is_won' => $stage->isWon,
        ];
    }
}
