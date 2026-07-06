<?php
declare(strict_types=1);

namespace App\Schema;

final class UserSchema
{
    const TABLE    = 'users';
    const ID       = 'id';
    const NAME     = 'full_name';
    const EMAIL    = 'email';
    const PASSWORD = 'password_hash';
    const ROLE     = 'role';
    const ACTIVE   = 'is_active';
    const CREATED  = 'created_at';

    /** Returns all selectable columns as comma-separated string, optionally excluding some */
    public static function columns(array $exclude = []): string
    {
        $all = [self::ID, self::NAME, self::EMAIL, self::ROLE, self::ACTIVE, self::CREATED];
        return implode(', ', array_diff($all, $exclude));
    }

    /** Validation rules, usable by Validator */
    public static function rules(): array
    {
        return [
            self::NAME  => 'required|string|max:255',
            self::EMAIL => 'required|email|max:255',
            self::ROLE  => 'required|in:admin,user,viewer',
        ];
    }
}
