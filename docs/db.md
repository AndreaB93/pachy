# DB Wrapper

## Basic Usage

```php
use Core\DB;

// SELECT all rows
$users = DB::query("SELECT * FROM users WHERE role = ?", ['admin']);

// SELECT first row or null
$user = DB::row("SELECT * FROM users WHERE id = ?", [$id]);

// SELECT single scalar
$count = DB::value("SELECT COUNT(*) FROM users");

// INSERT / UPDATE / DELETE
$affected = DB::execute("UPDATE users SET is_active = 1 WHERE id = ?", [$id]);

// Last inserted ID
$newId = DB::lastId();

// Transaction (auto-rollback on exception)
$result = DB::transaction(function() {
    DB::execute("INSERT INTO orders ...");
    DB::execute("UPDATE inventory ...");
    return DB::lastId();
});

// Bulk insert
DB::insertBatch('log_entries', $rows, chunkSize: 200);
```

## Schema Classes

Schema classes centralize all table and column name constants.

```php
use App\Schema\UserSchema as S;

$users = DB::query(
    "SELECT " . S::columns([S::PASSWORD]) . " FROM " . S::TABLE . " WHERE " . S::ROLE . " = ?",
    ['admin']
);
```

**Rules:**
- One Schema class per DB table.
- All classes are `final`.
- No SQL strings inside Schema classes.
- `columns(array $exclude = [])` returns a comma-separated column list.

## Repository Pattern

```php
// ✅ Correct — SQL lives in the Repository
class UserRepository
{
    public function findActive(): array
    {
        return DB::query("SELECT ... FROM users WHERE is_active = 1");
    }
}

// ❌ Wrong — never write SQL in a Controller or Service
$users = DB::query("SELECT * FROM users"); // don't do this outside a Repository
```

## Rules

- `ATTR_EMULATE_PREPARES = false` always
- `ATTR_ERRMODE = ERRMODE_EXCEPTION` always
- `ATTR_DEFAULT_FETCH_MODE = FETCH_ASSOC` always
- charset: `utf8mb4`
- Never call `DB::pdo()` outside of `core/` directory
