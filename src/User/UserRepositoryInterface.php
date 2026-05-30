<?php

declare(strict_types=1);

namespace NeneDeal\User;

/**
 * Persistence for operator accounts.
 *
 * Identity lookups run on holder-less paths — login (pre-auth) and "current
 * user" resolution from the token `sub` — so they are deliberately NOT scoped
 * to the request organization. Email is globally unique.
 */
interface UserRepositoryInterface
{
    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;
}
