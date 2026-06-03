<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

/**
 * One entry in a deal's audit trail. Records every meaningful change so the
 * team can always see who did what and when.
 *
 * `action` is one of: created, updated, stage_changed, deleted, restored,
 * handoff. `fromStageId`/`toStageId` are set for `stage_changed` (and the
 * initial `created`). `changes` is a field => {from, to} map for `updated`.
 */
final readonly class DealActivity
{
    /** @param array<string, array{from: mixed, to: mixed}>|null $changes */
    public function __construct(
        public string $id,
        public string $dealId,
        public string $action,
        public ?string $fromStageId = null,
        public ?string $toStageId = null,
        public ?string $actorUserId = null,
        public ?array $changes = null,
        public ?string $createdAt = null,
        /** Read-only display label (actor's email), populated on reads. */
        public ?string $actorLabel = null,
    ) {
    }
}
