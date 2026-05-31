<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

final readonly class CreateStageInput
{
    public function __construct(
        public string $label,
        public int $sortOrder,
    ) {
    }
}
