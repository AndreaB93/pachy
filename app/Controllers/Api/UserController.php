<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\DTOs\{CreateUserDTO, UpdateUserDTO};
use App\Services\UserService;
use Core\{Auth, Request, Response};

class UserController
{
    private UserService $service;

    public function __construct()
    {
        global $container;
        $this->service = $container->make(UserService::class);
    }

    /** GET /api/users */
    public function index(Request $request): void
    {
        Auth::requireBearer();

        $filters = array_filter([
            'role'   => $request->query('role'),
            'active' => $request->query('active'),
        ]);

        $users = $this->service->list($filters);
        Response::json(['data' => $users]);
    }

    /** GET /api/users/{id} */
    public function show(Request $request): void
    {
        Auth::requireBearer();

        try {
            $user = $this->service->getById((int)$request->param('id'));
            Response::json(['data' => $user]);
        } catch (\RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    /** POST /api/users */
    public function store(Request $request): void
    {
        Auth::requireBearer('admin');

        try {
            $dto  = CreateUserDTO::fromArray($request->body());
            $user = $this->service->register($dto);
            Response::json(['data' => $user], 201);
        } catch (\InvalidArgumentException $e) {
            Response::json(['errors' => json_decode($e->getMessage(), true)], 422);
        } catch (\DomainException $e) {
            Response::json(['error' => $e->getMessage()], 409);
        }
    }

    /** PUT /api/users/{id} */
    public function update(Request $request): void
    {
        Auth::requireBearer('admin');

        try {
            $dto  = UpdateUserDTO::fromArray((int)$request->param('id'), $request->body());
            $user = $this->service->update($dto);
            Response::json(['data' => $user]);
        } catch (\InvalidArgumentException $e) {
            Response::json(['errors' => json_decode($e->getMessage(), true)], 422);
        } catch (\RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /** DELETE /api/users/{id} */
    public function destroy(Request $request): void
    {
        Auth::requireBearer('admin');

        try {
            $this->service->delete((int)$request->param('id'));
            Response::json(['message' => 'User deleted.']);
        } catch (\RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /** POST /api/auth/login — returns JWT token */
    public function login(Request $request): void
    {
        try {
            $user  = $this->service->authenticate(
                $request->body('email', ''),
                $request->body('password', '')
            );
            $token = Auth::generateToken($user);
            Response::json(['token' => $token, 'expires_in' => (int)($_ENV['JWT_TTL'] ?? 3600)]);
        } catch (\RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], 401);
        }
    }
}
