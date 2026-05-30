<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use RuntimeException;

final class DealNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Deal {$id} not found.");
    }
}
