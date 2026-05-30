<?php

declare(strict_types=1);

namespace NeneDeal\Auth;

use Nene2\Auth\TokenIssuerInterface;
use NeneDeal\User\UserRepositoryInterface;

/**
 * Authenticates an operator by email + password and issues a bearer token
 * carrying the user id (`sub`), role, and organization id.
 */
final readonly class LoginUseCase
{
    private const TOKEN_TTL_SECONDS = 3600;

    public function __construct(
        private UserRepositoryInterface $users,
        private TokenIssuerInterface $tokenIssuer,
    ) {
    }

    /** @throws InvalidCredentialsException */
    public function execute(LoginInput $input): LoginOutput
    {
        $user = $this->users->findByEmail($input->email);

        if ($user === null || !password_verify($input->password, $user->passwordHash)) {
            throw new InvalidCredentialsException();
        }

        $now = time();
        $token = $this->tokenIssuer->issue([
            'sub' => $user->id,
            'role' => $user->role,
            'org' => $user->organizationId,
            'iat' => $now,
            'exp' => $now + self::TOKEN_TTL_SECONDS,
        ]);

        return new LoginOutput($token);
    }
}
