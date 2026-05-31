<?php

declare(strict_types=1);

namespace NeneDeal\User;

use RuntimeException;

final class UserNotFoundException extends RuntimeException
{
    public function __construct(string $userId)
    {
        parent::__construct(sprintf('User "%s" not found.', $userId));
    }
}
