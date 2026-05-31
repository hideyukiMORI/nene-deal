<?php

declare(strict_types=1);

namespace NeneDeal\User;

use RuntimeException;

final class EmailAlreadyTakenException extends RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('Email "%s" is already taken.', $email));
    }
}
