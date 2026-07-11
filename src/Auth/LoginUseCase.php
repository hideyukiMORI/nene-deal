<?php

declare(strict_types=1);

namespace NeneDeal\Auth;

use Nene2\Auth\TokenIssuerInterface;
use Nene2\Http\ClockInterface;
use NeneDeal\User\UserRepositoryInterface;
use NeneDeal\User\UserStatus;

/**
 * Authenticates an operator by email + password and issues a bearer token
 * carrying the user id (`sub`), role, and organization id.
 *
 * Only `active` accounts may log in (#90). Unknown email, wrong password, and
 * disabled account are all rejected with the same generic error so account
 * existence/status is not disclosed.
 */
final readonly class LoginUseCase
{
    private const TOKEN_TTL_SECONDS = 3600;

    /**
     * A valid bcrypt hash used to equalize timing when the email is unknown,
     * so a failed login does not reveal whether the account exists
     * (clear-shape timing equalizer).
     */
    private const TIMING_EQUALIZER_HASH = '$2y$12$zIm0IdtQKFLbeCP4lZhm7upwJ7hz/JAj4krfZ53eGCIVzLq82RwP6';

    public function __construct(
        private UserRepositoryInterface $users,
        private TokenIssuerInterface $tokenIssuer,
        private ClockInterface $clock,
    ) {
    }

    /** @throws InvalidCredentialsException */
    public function execute(LoginInput $input): LoginOutput
    {
        $user = $this->users->findByEmail($input->email);

        $hash = $user !== null ? $user->passwordHash : self::TIMING_EQUALIZER_HASH;
        $passwordMatches = password_verify($input->password, $hash);

        if ($user === null || !$passwordMatches || $user->status !== UserStatus::Active) {
            throw new InvalidCredentialsException();
        }

        $now = $this->clock->now()->getTimestamp();
        $token = $this->tokenIssuer->issue([
            'sub' => $user->id,
            'role' => $user->role->value,
            'org' => $user->organizationId,
            'iat' => $now,
            'exp' => $now + self::TOKEN_TTL_SECONDS,
        ]);

        return new LoginOutput($token);
    }
}
