<?php
declare(strict_types=1);

namespace Core;

class Validator
{
    /** @throws \InvalidArgumentException with JSON-encoded field errors */
    public function validate(array $data, array $rules): void
    {
        $errors = [];
        foreach ($rules as $field => $ruleString) {
            $value      = $data[$field] ?? null;
            $fieldRules = explode('|', $ruleString);
            foreach ($fieldRules as $rule) {
                $error = $this->applyRule($field, $value, $rule);
                if ($error !== null) {
                    $errors[$field][] = $error;
                }
            }
        }
        if ($errors !== []) {
            throw new \InvalidArgumentException(json_encode($errors));
        }
    }

    private function applyRule(string $field, mixed $value, string $rule): ?string
    {
        [$ruleName, $param] = [...explode(':', $rule, 2), null];

        return match($ruleName) {
            'required' => (empty($value) && $value !== '0') ? "$field is required." : null,
            'nullable' => null,
            'string'   => $value !== null && !is_string($value) ? "$field must be a string." : null,
            'int'      => $value !== null && !is_numeric($value) ? "$field must be an integer." : null,
            'float'    => $value !== null && !is_numeric($value) ? "$field must be a number." : null,
            'email'    => $value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL) ? "$field must be a valid email." : null,
            'min'      => $value !== null && $value !== '' && strlen((string)$value) < (int)$param ? "$field must be at least $param chars." : null,
            'max'      => $value !== null && $value !== '' && strlen((string)$value) > (int)$param ? "$field must be at most $param chars." : null,
            'in'       => $value !== null && $value !== '' && !in_array($value, explode(',', (string)$param), true) ? "$field must be one of: $param." : null,
            'regex'    => $value !== null && $value !== '' && !preg_match((string)$param, (string)$value) ? "$field format is invalid." : null,
            default    => null,
        };
    }
}
