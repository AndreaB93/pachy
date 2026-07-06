<?php

return [
    'session' => [
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? '',
        'ttl'    => (int)($_ENV['JWT_TTL'] ?? 3600),
        'algo'   => 'HS256',
    ],
];
