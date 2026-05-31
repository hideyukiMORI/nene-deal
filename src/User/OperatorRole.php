<?php

declare(strict_types=1);

namespace NeneDeal\User;

enum OperatorRole: string
{
    case Admin = 'admin';
    case Operator = 'operator';
}
