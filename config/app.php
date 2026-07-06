<?php

return [
    'name'     => $_ENV['APP_NAME'] ?? 'App',
    'debug'    => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    'timezone' => 'Europe/Rome',
    'locale'   => 'it_IT',
];
