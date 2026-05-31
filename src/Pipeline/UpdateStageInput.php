<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

final readonly class UpdateStageInput
{
    public function __construct(
        public ?string $label = null,
        public ?int $sortOrder = null,
    ) {
    }
}
