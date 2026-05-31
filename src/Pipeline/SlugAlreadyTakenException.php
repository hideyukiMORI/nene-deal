<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use RuntimeException;

final class SlugAlreadyTakenException extends RuntimeException
{
    public function __construct(string $slug)
    {
        parent::__construct(sprintf('A stage with slug "%s" already exists.', $slug));
    }
}
