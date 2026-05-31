<?php

declare(strict_types=1);

namespace NeneDeal\User;

final class UserResponse
{
    /** @return array<string, mixed> */
    public static function toArray(User $user): array
    {
        return [
            'id' => $user->id,
            'organization_id' => $user->organizationId,
            'email' => $user->email,
            'role' => $user->role->value,
            'created_at' => $user->createdAt,
            'updated_at' => $user->updatedAt,
        ];
    }
}
