<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Support;

use NeneDeal\Tenancy\OrganizationResolver;

/**
 * In-memory {@see OrganizationResolver} for middleware tests.
 */
final class FakeOrganizationResolver implements OrganizationResolver
{
    /**
     * @param array<string, string> $bySlug slug => id
     */
    public function __construct(
        private readonly array $bySlug = [],
        private readonly ?string $soleId = null,
    ) {
    }

    public function soleId(): ?string
    {
        return $this->soleId;
    }

    public function findIdBySlug(string $slug): ?string
    {
        return $this->bySlug[$slug] ?? null;
    }
}
