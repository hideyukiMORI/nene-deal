<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use RuntimeException;

final class StageHasDealsException extends RuntimeException
{
    public function __construct(string $stageId)
    {
        parent::__construct(sprintf('Stage "%s" has deals and cannot be deleted.', $stageId));
    }
}
