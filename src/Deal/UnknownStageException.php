<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use RuntimeException;

/**
 * Raised when a create / stage-change request references a stage that does not
 * exist in the organization. Surfaced as a 422 validation problem.
 */
final class UnknownStageException extends RuntimeException
{
    public function __construct(
        public readonly string $stageRef,
    ) {
        parent::__construct("Unknown stage \"{$stageRef}\".");
    }
}
