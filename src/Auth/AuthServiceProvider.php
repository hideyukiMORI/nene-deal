<?php

declare(strict_types=1);

namespace NeneDeal\Auth;

use LogicException;
use Nene2\Auth\TokenIssuerInterface;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use NeneDeal\User\UserRepositoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Wires the operator authentication slice: user repository, login + current-user
 * use cases, handlers, the invalid-credentials exception handler, and routes.
 */
final readonly class AuthServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                LoginUseCase::class,
                static function (ContainerInterface $c): LoginUseCase {
                    $tokenIssuer = $c->get(TokenIssuerInterface::class);

                    if (!$tokenIssuer instanceof TokenIssuerInterface) {
                        throw new LogicException('Token issuer service is invalid.');
                    }

                    $clock = $c->get(ClockInterface::class);

                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('Clock service is invalid.');
                    }

                    return new LoginUseCase(self::users($c), $tokenIssuer, $clock);
                },
            )
            ->set(
                LoginThrottleInterface::class,
                static function (ContainerInterface $c): LoginThrottleInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $clock = $c->get(ClockInterface::class);

                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('Clock service is invalid.');
                    }

                    return new PdoLoginThrottle($query, $clock);
                },
            )
            ->set(
                LoginHandler::class,
                static function (ContainerInterface $c): LoginHandler {
                    $useCase = $c->get(LoginUseCase::class);

                    if (!$useCase instanceof LoginUseCase) {
                        throw new LogicException('Login use case service is invalid.');
                    }

                    $throttle = $c->get(LoginThrottleInterface::class);

                    if (!$throttle instanceof LoginThrottleInterface) {
                        throw new LogicException('Login throttle service is invalid.');
                    }

                    return new LoginHandler($useCase, self::json($c), self::problem($c), $throttle);
                },
            )
            ->set(
                GetCurrentUserUseCase::class,
                static fn (ContainerInterface $c): GetCurrentUserUseCase => new GetCurrentUserUseCase(self::users($c)),
            )
            ->set(
                MeHandler::class,
                static function (ContainerInterface $c): MeHandler {
                    $useCase = $c->get(GetCurrentUserUseCase::class);

                    if (!$useCase instanceof GetCurrentUserUseCase) {
                        throw new LogicException('Get current user use case service is invalid.');
                    }

                    return new MeHandler($useCase, self::json($c), self::problem($c));
                },
            )
            ->set(
                InvalidCredentialsExceptionHandler::class,
                static fn (ContainerInterface $c): InvalidCredentialsExceptionHandler => new InvalidCredentialsExceptionHandler(self::problem($c)),
            )
            ->set(
                TooManyLoginAttemptsExceptionHandler::class,
                static fn (ContainerInterface $c): TooManyLoginAttemptsExceptionHandler => new TooManyLoginAttemptsExceptionHandler(self::problem($c)),
            )
            ->set(
                AuthRouteRegistrar::class,
                static function (ContainerInterface $c): AuthRouteRegistrar {
                    $login = $c->get(LoginHandler::class);
                    $me = $c->get(MeHandler::class);

                    if (!$login instanceof LoginHandler || !$me instanceof MeHandler) {
                        throw new LogicException('Auth handler services are invalid.');
                    }

                    return new AuthRouteRegistrar($login, $me);
                },
            );
    }

    private static function users(ContainerInterface $c): UserRepositoryInterface
    {
        $repo = $c->get(UserRepositoryInterface::class);

        if (!$repo instanceof UserRepositoryInterface) {
            throw new LogicException('User repository service is invalid.');
        }

        return $repo;
    }

    private static function json(ContainerInterface $c): JsonResponseFactory
    {
        $j = $c->get(JsonResponseFactory::class);

        if (!$j instanceof JsonResponseFactory) {
            throw new LogicException('JSON response factory service is invalid.');
        }

        return $j;
    }

    private static function problem(ContainerInterface $c): ProblemDetailsResponseFactory
    {
        $p = $c->get(ProblemDetailsResponseFactory::class);

        if (!$p instanceof ProblemDetailsResponseFactory) {
            throw new LogicException('Problem details response factory service is invalid.');
        }

        return $p;
    }
}
