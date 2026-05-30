<?php

declare(strict_types=1);

namespace NeneDeal\Tenancy;

/**
 * A {@see CurrentOrganization} that always returns a preset id. Used by tests
 * (and any single-fixed-tenant scenario) to scope repositories deterministically.
 */
final readonly class FixedOrganization implements CurrentOrganization
{
    public function __construct(
        private string $id,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }
}
