<?php

declare(strict_types=1);

namespace NeneDeal\Handoff;

use RuntimeException;

/**
 * Raised on any transport, configuration, or non-2xx upstream condition while
 * talking to NeNe Invoice. The deal is left won but unlinked. Surfaced as a 502.
 */
final class InvoiceHandoffException extends RuntimeException
{
}
