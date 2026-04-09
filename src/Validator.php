<?php

declare(strict_types=1);

namespace App;

/**
 * Validator — simple rule-based input validator.
 *
 * Usage:
 *   $v = new Validator($request['body'], [
 *       'name'  => 'required|min:2|max:120',
 *       'email' => 'required|email',
 *       'age'   => 'integer|min:0',
 *   ]);
 *
 *   if (!$v->passes()) {
 *       (new Response())->json(['errors' => $v->errors()], 422);
 *       return;
 *   }
 *
 * Supported rules:
 *   required, string, integer, float, boolean,
 *   email, url, min:{n}, max:{n}, in:{a,b,c}
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data  = $data;
        $this->rules = $rules;
    }

    public function passes(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /** @return array<string, string[]> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** Return the first error message for a field, or null. */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

        match ($name) {
            'required' => $this->checkRequired($field, $value),
            'string'   => $this->checkType($field, $value, 'string'),
            'integer'  => $this->checkType($field, $value, 'integer'),
            'float'    => $this->checkType($field, $value, 'float'),
            'boolean'  => $this->checkType($field, $value, 'boolean'),
            'email'    => $this->checkEmail($field, $value),
            'url'      => $this->checkUrl($field, $value),
            'min'      => $this->checkMin($field, $value, (int) $param),
            'max'      => $this->checkMax($field, $value, (int) $param),
            'in'       => $this->checkIn($field, $value, explode(',', $param ?? '')),
            default    => null,
        };
    }

    private function checkRequired(string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->addError($field, "{$field} is required.");
        }
    }

    private function checkType(string $field, mixed $value, string $type): void
    {
        if ($value === null) {
            return;
        }
        $ok = match ($type) {
            'string'  => is_string($value),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'float'   => filter_var($value, FILTER_VALIDATE_FLOAT) !== false,
            'boolean' => is_bool($value) || in_array($value, ['true', 'false', '1', '0', 1, 0], true),
            default   => true,
        };
        if (!$ok) {
            $this->addError($field, "{$field} must be a {$type}.");
        }
    }

    private function checkEmail(string $field, mixed $value): void
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "{$field} must be a valid email address.");
        }
    }

    private function checkUrl(string $field, mixed $value): void
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, "{$field} must be a valid URL.");
        }
    }

    private function checkMin(string $field, mixed $value, int $min): void
    {
        if ($value === null) {
            return;
        }
        if (is_string($value) && mb_strlen($value) < $min) {
            $this->addError($field, "{$field} must be at least {$min} characters.");
        } elseif (is_numeric($value) && (float) $value < $min) {
            $this->addError($field, "{$field} must be at least {$min}.");
        }
    }

    private function checkMax(string $field, mixed $value, int $max): void
    {
        if ($value === null) {
            return;
        }
        if (is_string($value) && mb_strlen($value) > $max) {
            $this->addError($field, "{$field} must not exceed {$max} characters.");
        } elseif (is_numeric($value) && (float) $value > $max) {
            $this->addError($field, "{$field} must not exceed {$max}.");
        }
    }

    private function checkIn(string $field, mixed $value, array $allowed): void
    {
        if ($value !== null && !in_array((string) $value, $allowed, true)) {
            $list = implode(', ', $allowed);
            $this->addError($field, "{$field} must be one of: {$list}.");
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
