<?php
declare(strict_types=1);

use Core\{Container, Validator};
use App\Repositories\UserRepository;
use App\Services\UserService;

/**
 * Register singleton bindings in the DI container.
 * $container is injected from bootstrap.php.
 *
 * @var Container $container
 */

$container->singleton(Validator::class, fn() => new Validator());

$container->singleton(UserRepository::class, fn() => new UserRepository());

$container->singleton(UserService::class, fn(Container $c) => new UserService(
    $c->make(UserRepository::class),
    $c->make(Validator::class),
));
