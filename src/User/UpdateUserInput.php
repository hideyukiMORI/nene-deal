<?php

declare(strict_types=1);

namespace NeneDeal\User;

final readonly class UpdateUserInput
{
    public function __construct(
        public ?string $email = null,
        public ?OperatorRole $role = null,
        public ?UserStatus $status = null,
    ) {
    }
}
