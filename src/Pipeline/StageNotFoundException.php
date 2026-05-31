<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use RuntimeException;

final class StageNotFoundException extends RuntimeException
{
    public function __construct(string $stageId)
    {
        parent::__construct(sprintf('Stage "%s" not found.', $stageId));
    }
}
