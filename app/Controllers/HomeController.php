<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\{Auth, Request, Response};

class HomeController
{
    public function index(Request $request): void
    {
        $user = Auth::sessionUser();
        Response::view('pages/home', ['user' => $user]);
    }
}
