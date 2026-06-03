<?php

declare(strict_types=1);

namespace NeneDeal\Settings;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneDeal\Tenancy\CurrentOrganization;

final readonly class PdoOrganizationSettingsRepository implements OrganizationSettingsRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private CurrentOrganization $organization,
    ) {
    }

    public function forecastClosingDay(): ?int
    {
        $row = $this->query->fetchOne(
            'SELECT forecast_closing_day FROM organizations WHERE id = ?',
            [$this->organization->id()],
        );

        // isset() is false for both an absent key and a NULL value.
        if ($row === null || !isset($row['forecast_closing_day'])) {
            return null;
        }

        return (int) $row['forecast_closing_day'];
    }

    public function updateForecastClosingDay(?int $day): void
    {
        $this->query->execute(
            'UPDATE organizations SET forecast_closing_day = ?, updated_at = ? WHERE id = ?',
            [$day, date('Y-m-d H:i:s'), $this->organization->id()],
        );
    }
}
