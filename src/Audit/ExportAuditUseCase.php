<?php

declare(strict_types=1);

namespace NeneDeal\Audit;

final readonly class ExportAuditUseCase
{
    public function __construct(
        private AuditExportRepositoryInterface $activity,
    ) {
    }

    /**
     * @return list<AuditExportRow>
     */
    public function execute(string $startDateTime, string $endDateTime): array
    {
        return $this->activity->findInRange($startDateTime, $endDateTime);
    }
}
