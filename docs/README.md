# pachy — Quick Start

pachy is a minimal PHP 8.5 architectural framework. No ORM, no magic, no lock-in.

## Requirements

- PHP 8.2+ (8.5 recommended)
- Composer
- MySQL / MariaDB / SQLite
- Apache or Nginx (shared hosting compatible)

## Installation

```bash
git clone https://github.com/you/pachy-app.git my-app
cd my-app
composer install
cp .env.example .env
# Edit .env with your DB credentials and JWT_SECRET
```

## First Route

1. Add to `app/routes.php`:

```php
$router->get('/hello', [[App\Controllers\HelloController::class, 'index']]);
```

2. Create `app/Controllers/HelloController.php`:

```php
namespace App\Controllers;

use Core\{Request, Response};

class HelloController
{
    public function index(Request $request): void
    {
        Response::json(['message' => 'Hello, pachy!']);
    }
}
```

3. Point your web server to `public/` and visit `/hello`.

## Run Migrations

```bash
php cli/migrate.php
```

## Run Tests

```bash
vendor/bin/phpunit
```

## Crontab (Scheduler)

```
* * * * * php /path/to/cli/scheduler.php >> /path/to/storage/logs/scheduler.log 2>&1
```
