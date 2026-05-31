<?php

declare(strict_types=1);

namespace NeneDeal\User;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneDeal\Auth\RequireRoleMiddleware;
use NeneDeal\Tenancy\CurrentOrganization;
use Psr\Container\ContainerInterface;

/**
 * Wires the user management slice: repository, use cases, handlers, exception
 * handlers, the admin-role middleware, and the route registrar.
 */
final readonly class UserServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                UserRepositoryInterface::class,
                static function (ContainerInterface $c): UserRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoUserRepository($query);
                },
            )
            ->set(
                ListUsersUseCase::class,
                static function (ContainerInterface $c): ListUsersUseCase {
                    return new ListUsersUseCase(self::repo($c), self::org($c));
                },
            )
            ->set(
                CreateUserUseCase::class,
                static function (ContainerInterface $c): CreateUserUseCase {
                    return new CreateUserUseCase(self::repo($c), self::org($c));
                },
            )
            ->set(
                UpdateUserUseCase::class,
                static function (ContainerInterface $c): UpdateUserUseCase {
                    return new UpdateUserUseCase(self::repo($c), self::org($c));
                },
            )
            ->set(
                DeleteUserUseCase::class,
                static function (ContainerInterface $c): DeleteUserUseCase {
                    return new DeleteUserUseCase(self::repo($c), self::org($c));
                },
            )
            ->set(
                ListUsersHandler::class,
                static function (ContainerInterface $c): ListUsersHandler {
                    $useCase = $c->get(ListUsersUseCase::class);

                    if (!$useCase instanceof ListUsersUseCase) {
                        throw new LogicException('List users use case service is invalid.');
                    }

                    return new ListUsersHandler($useCase, self::json($c));
                },
            )
            ->set(
                CreateUserHandler::class,
                static function (ContainerInterface $c): CreateUserHandler {
                    $useCase = $c->get(CreateUserUseCase::class);

                    if (!$useCase instanceof CreateUserUseCase) {
                        throw new LogicException('Create user use case service is invalid.');
                    }

                    return new CreateUserHandler($useCase, self::json($c), self::problem($c));
                },
            )
            ->set(
                GetUserHandler::class,
                static function (ContainerInterface $c): GetUserHandler {
                    return new GetUserHandler(self::repo($c), self::org($c), self::json($c));
                },
            )
            ->set(
                UpdateUserHandler::class,
                static function (ContainerInterface $c): UpdateUserHandler {
                    $useCase = $c->get(UpdateUserUseCase::class);

                    if (!$useCase instanceof UpdateUserUseCase) {
                        throw new LogicException('Update user use case service is invalid.');
                    }

                    return new UpdateUserHandler($useCase, self::json($c), self::problem($c));
                },
            )
            ->set(
                DeleteUserHandler::class,
                static function (ContainerInterface $c): DeleteUserHandler {
                    $useCase = $c->get(DeleteUserUseCase::class);

                    if (!$useCase instanceof DeleteUserUseCase) {
                        throw new LogicException('Delete user use case service is invalid.');
                    }

                    return new DeleteUserHandler($useCase, self::json($c));
                },
            )
            ->set(
                RequireRoleMiddleware::class,
                static function (ContainerInterface $c): RequireRoleMiddleware {
                    return new RequireRoleMiddleware(OperatorRole::Admin, self::problem($c));
                },
            )
            ->set(
                UserRouteRegistrar::class,
                static function (ContainerInterface $c): UserRouteRegistrar {
                    $requireAdmin = $c->get(RequireRoleMiddleware::class);
                    $list = $c->get(ListUsersHandler::class);
                    $create = $c->get(CreateUserHandler::class);
                    $get = $c->get(GetUserHandler::class);
                    $update = $c->get(UpdateUserHandler::class);
                    $delete = $c->get(DeleteUserHandler::class);

                    if (!$requireAdmin instanceof RequireRoleMiddleware) {
                        throw new LogicException('Require role middleware service is invalid.');
                    }

                    if (!$list instanceof ListUsersHandler) {
                        throw new LogicException('List users handler service is invalid.');
                    }

                    if (!$create instanceof CreateUserHandler) {
                        throw new LogicException('Create user handler service is invalid.');
                    }

                    if (!$get instanceof GetUserHandler) {
                        throw new LogicException('Get user handler service is invalid.');
                    }

                    if (!$update instanceof UpdateUserHandler) {
                        throw new LogicException('Update user handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteUserHandler) {
                        throw new LogicException('Delete user handler service is invalid.');
                    }

                    return new UserRouteRegistrar($list, $create, $get, $update, $delete, $requireAdmin);
                },
            )
            ->set(
                UserNotFoundExceptionHandler::class,
                static fn (ContainerInterface $c): UserNotFoundExceptionHandler => new UserNotFoundExceptionHandler(self::problem($c)),
            )
            ->set(
                EmailAlreadyTakenExceptionHandler::class,
                static fn (ContainerInterface $c): EmailAlreadyTakenExceptionHandler => new EmailAlreadyTakenExceptionHandler(self::problem($c)),
            )
            ->set(
                CannotModifySelfExceptionHandler::class,
                static fn (ContainerInterface $c): CannotModifySelfExceptionHandler => new CannotModifySelfExceptionHandler(self::problem($c)),
            );
    }

    private static function repo(ContainerInterface $c): UserRepositoryInterface
    {
        $repo = $c->get(UserRepositoryInterface::class);

        if (!$repo instanceof UserRepositoryInterface) {
            throw new LogicException('User repository service is invalid.');
        }

        return $repo;
    }

    private static function org(ContainerInterface $c): CurrentOrganization
    {
        $org = $c->get(CurrentOrganization::class);

        if (!$org instanceof CurrentOrganization) {
            throw new LogicException('Current organization service is invalid.');
        }

        return $org;
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
