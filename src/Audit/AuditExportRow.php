<?php

declare(strict_types=1);

namespace NeneDeal\Audit;

/**
 * One flattened audit-trail row for CSV export, enriched with the deal label,
 * actor email and stage labels so the file is readable without lookups.
 */
final readonly class AuditExportRow
{
    /** @param array<string, array{from: mixed, to: mixed}>|null $changes */
    public function __construct(
        public string $createdAt,
        public string $action,
        public string $dealId,
        public string $dealLabel,
        public ?string $actorLabel,
        public ?string $fromStageLabel,
        public ?string $toStageLabel,
        public ?array $changes,
    ) {
    }
}
