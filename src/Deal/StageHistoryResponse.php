<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

/**
 * Serializes a {@see StageHistoryEntry} to its snake_case JSON representation
 * (see the StageHistoryEntry schema in docs/openapi/openapi.yaml).
 */
final class StageHistoryResponse
{
    /** @return array<string, mixed> */
    public static function toArray(StageHistoryEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'deal_id' => $entry->dealId,
            'from_stage_id' => $entry->fromStageId,
            'to_stage_id' => $entry->toStageId,
            'actor_user_id' => $entry->actorUserId,
            'created_at' => $entry->createdAt,
        ];
    }
}
