<?php

declare(strict_types=1);

namespace NeneDeal\User;

/**
 * Account status. Disabled accounts cannot log in and are rejected on
 * bearer-token resolution; the row (and its stage-history attribution) is
 * preserved. Disabling is the supported alternative to deletion (#90).
 */
enum UserStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
