# DTOs

Data Transfer Objects are **typed, immutable** data carriers between layers.

## Conventions

- Named `{Action}{Entity}DTO` (e.g. `CreateUserDTO`, `UpdateOrderDTO`)
- All DTOs are `readonly` classes (PHP 8.2+)
- Expose a static `fromArray()` factory for HTTP input
- No dependencies, no side effects, no DB access
- Carry only data — no methods that do work

## Example

```php
readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $role = 'user',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:     trim($data['name'] ?? ''),
            email:    strtolower(trim($data['email'] ?? '')),
            password: $data['password'] ?? '',
            role:     $data['role'] ?? 'user',
        );
    }
}
```

## Usage in Controller

```php
$dto = CreateUserDTO::fromArray($request->body());
$user = $this->service->register($dto);
```

## PHP 8.5: `clone with`

For partial updates of readonly DTOs:

```php
$promoted = clone $dto with { role: 'admin' };
```

This avoids reconstructing the entire DTO when only one field changes.

## Validation

DTOs are validated in the Service layer using `Validator` against `Schema::rules()`:

```php
$this->validator->validate((array) $dto, UserSchema::rules());
```

Validation errors throw `\InvalidArgumentException` with JSON-encoded field errors.
