<?php
declare(strict_types=1);

namespace App\Controllers;

use App\DTOs\CreateUserDTO;
use App\Services\UserService;
use Core\{Auth, Request, Response};

class AuthController
{
    private UserService $service;

    public function __construct()
    {
        global $container;
        $this->service = $container->make(UserService::class);
    }

    public function showLogin(Request $request): void
    {
        if (Auth::sessionUser()) {
            Response::redirect('/');
        }
        Response::view('pages/login', [
            'csrf' => Auth::csrfToken(),
        ]);
    }

    public function login(Request $request): void
    {
        $token = $request->body('_csrf', '');
        if (!Auth::verifyCsrf($token)) {
            Response::view('pages/login', ['error' => 'Invalid CSRF token.', 'csrf' => Auth::csrfToken()]);
        }

        try {
            $user = $this->service->authenticate(
                $request->body('email', ''),
                $request->body('password', '')
            );
            Auth::loginSession($user);
            Response::redirect('/');
        } catch (\RuntimeException $e) {
            Response::view('pages/login', [
                'error' => 'Email o password non validi.',
                'csrf'  => Auth::csrfToken(),
            ]);
        }
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        Response::redirect('/login');
    }

    public function showRegister(Request $request): void
    {
        Response::view('pages/register', ['csrf' => Auth::csrfToken()]);
    }

    public function register(Request $request): void
    {
        $token = $request->body('_csrf', '');
        if (!Auth::verifyCsrf($token)) {
            Response::view('pages/register', ['error' => 'Invalid CSRF token.', 'csrf' => Auth::csrfToken()]);
        }

        try {
            $dto  = CreateUserDTO::fromArray($request->body());
            $user = $this->service->register($dto);
            Auth::loginSession($user);
            Response::redirect('/');
        } catch (\InvalidArgumentException $e) {
            Response::view('pages/register', [
                'errors' => json_decode($e->getMessage(), true),
                'csrf'   => Auth::csrfToken(),
            ]);
        } catch (\DomainException $e) {
            Response::view('pages/register', [
                'error' => $e->getMessage(),
                'csrf'  => Auth::csrfToken(),
            ]);
        }
    }
}
