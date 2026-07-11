<?php

declare(strict_types=1);

namespace NeneDeal\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `POST /api/v1/auth/login` — public. Exchanges email + password for a bearer token.
 *
 * Guarded by a clear-shape brute-force throttle keyed on email + client IP:
 * 5 failures within 15 minutes lock the identifier for 15 minutes (429). See
 * {@see LoginThrottleInterface} for the upstream-replacement note (#95).
 */
final readonly class LoginHandler implements RequestHandlerInterface
{
    public function __construct(
        private LoginUseCase $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
        private LoginThrottleInterface $throttle,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true);

        if (!is_array($body)) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, 'Request body must be a JSON object.');
        }

        $email = $body['email'] ?? null;
        $password = $body['password'] ?? null;

        if (!is_string($email) || $email === '' || !is_string($password) || $password === '') {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, 'Both "email" and "password" are required.');
        }

        $identifier = strtolower($email) . '|' . $this->clientIp($request);

        $secondsUntilUnlocked = $this->throttle->secondsUntilUnlocked($identifier);

        if ($secondsUntilUnlocked > 0) {
            throw new TooManyLoginAttemptsException($secondsUntilUnlocked);
        }

        try {
            $output = $this->useCase->execute(new LoginInput($email, $password));
        } catch (InvalidCredentialsException $e) {
            $this->throttle->recordFailure($identifier);

            throw $e;
        }

        $this->throttle->clear($identifier);

        return $this->json->create(['token' => $output->token]);
    }

    private function clientIp(ServerRequestInterface $request): string
    {
        $params = $request->getServerParams();

        return (string) ($params['REMOTE_ADDR'] ?? 'unknown');
    }
}
