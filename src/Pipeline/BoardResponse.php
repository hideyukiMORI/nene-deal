<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use NeneDeal\Deal\Deal;
use NeneDeal\Deal\DealResponse;

/**
 * Serializes a {@see KanbanBoard} to its JSON representation
 * (see the KanbanBoard schema in docs/openapi/openapi.yaml).
 */
final class BoardResponse
{
    /** @return array<string, mixed> */
    public static function toArray(KanbanBoard $board): array
    {
        $columns = array_map(
            static fn (KanbanColumn $column): array => [
                'stage' => PipelineStageResponse::toArray($column->stage),
                'deals' => array_map(
                    static fn (Deal $deal): array => DealResponse::toArray($deal),
                    $column->deals,
                ),
                'deal_count' => $column->dealCount,
                'total_cents' => $column->totalCents,
                'weighted_total_cents' => $column->weightedTotalCents,
            ],
            $board->columns,
        );

        return ['columns' => $columns];
    }
}
