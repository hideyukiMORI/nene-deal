<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use RuntimeException;

final class StageDeletionForbiddenException extends RuntimeException
{
    public function __construct(string $stageId)
    {
        parent::__construct(sprintf('Stage "%s" is terminal and cannot be deleted.', $stageId));
    }
}
