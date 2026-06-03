<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

/**
 * Serializes a {@see DealActivity} to its snake_case JSON representation
 * (see the DealActivity schema in docs/openapi/openapi.yaml).
 */
final class DealActivityResponse
{
    /** @return array<string, mixed> */
    public static function toArray(DealActivity $activity): array
    {
        return [
            'id' => $activity->id,
            'deal_id' => $activity->dealId,
            'action' => $activity->action,
            'from_stage_id' => $activity->fromStageId,
            'to_stage_id' => $activity->toStageId,
            'actor_user_id' => $activity->actorUserId,
            'actor_label' => $activity->actorLabel,
            'changes' => $activity->changes,
            'created_at' => $activity->createdAt,
        ];
    }
}
