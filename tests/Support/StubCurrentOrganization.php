<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Support;

use NeneDeal\Tenancy\CurrentOrganization;

final readonly class StubCurrentOrganization implements CurrentOrganization
{
    public function __construct(private string $organizationId)
    {
    }

    public function id(): string
    {
        return $this->organizationId;
    }
}
