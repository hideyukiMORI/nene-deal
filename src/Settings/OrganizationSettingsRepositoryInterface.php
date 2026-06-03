<?php

declare(strict_types=1);

namespace NeneDeal\Settings;

/**
 * Reads/writes organization-level settings, scoped to the current organization
 * via tenancy. Callers never pass an organization id.
 */
interface OrganizationSettingsRepositoryInterface
{
    /** Forecast closing day (1–28), or null for calendar month (month-end). */
    public function forecastClosingDay(): ?int;

    /** @param ?int $day 1–28, or null for calendar month. */
    public function updateForecastClosingDay(?int $day): void;
}
