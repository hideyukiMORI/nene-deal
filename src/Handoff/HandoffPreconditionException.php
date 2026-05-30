<?php

declare(strict_types=1);

namespace NeneDeal\Handoff;

use RuntimeException;

/**
 * Raised when a deal does not satisfy the handoff preconditions (not in the won
 * stage, or missing account label / amount). Surfaced as a 422.
 */
final class HandoffPreconditionException extends RuntimeException
{
}
