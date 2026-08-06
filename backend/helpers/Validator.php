<?php

declare(strict_types=1);

namespace AfriSense\Backend\Helpers;

class Validator
{
    private array $errors = [];

    /**
     * Validate input data against rules.
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        // Iterate through the data needed for this block.
        foreach ($rules as $field => $fieldRules) {
            $ruleList = is_array($fieldRules) ? $fieldRules : explode('|', (string) $fieldRules);
            $value = $data[$field] ?? null;

            // Iterate through the data needed for this block.
            foreach ($ruleList as $rule) {
                $this->applyRule((string) $field, $value, (string) $rule);
            }
        }

        return $this->errors === [];
    }

    /**
     * Return validation errors grouped by field.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        [$ruleName, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

        // Guard this block so it only runs when the required condition is met.
        if ($ruleName !== 'required' && ($value === null || $value === '')) {
            return;
        }

        match ($ruleName) {
            'required' => $this->validateRequired($field, $value),
            'email' => $this->validateEmail($field, $value),
            'password' => $this->validatePassword($field, $value),
            'phone' => $this->validatePhone($field, $value),
            'minLength' => $this->validateMinLength($field, $value, (int) $parameter),
            'maxLength' => $this->validateMaxLength($field, $value, (int) $parameter),
            default => null,
        };
    }

    private function validateRequired(string $field, mixed $value): void
    {
        // Guard this block so it only runs when the required condition is met.
        if ($value === null || trim((string) $value) === '') {
            $this->addError($field, sprintf('%s is required.', $field));
        }
    }

    private function validateEmail(string $field, mixed $value): void
    {
        // Guard this block so it only runs when the required condition is met.
        if (!filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, sprintf('%s must be a valid email address.', $field));
        }
    }

    private function validatePassword(string $field, mixed $value): void
    {
        $password = (string) $value;

        // Guard this block so it only runs when the required condition is met.
        if (
            strlen($password) < 8
            || !preg_match('/[A-Z]/', $password)
            || !preg_match('/[a-z]/', $password)
            || !preg_match('/[0-9]/', $password)
        ) {
            $this->addError(
                $field,
                sprintf('%s must be at least 8 characters and include upper, lower, and numeric characters.', $field)
            );
        }
    }

    private function validatePhone(string $field, mixed $value): void
    {
        // Guard this block so it only runs when the required condition is met.
        if (!preg_match('/^\+?[0-9\s\-()]{7,20}$/', (string) $value)) {
            $this->addError($field, sprintf('%s must be a valid phone number.', $field));
        }
    }

    private function validateMinLength(string $field, mixed $value, int $length): void
    {
        // Guard this block so it only runs when the required condition is met.
        if (mb_strlen((string) $value) < $length) {
            $this->addError($field, sprintf('%s must be at least %d characters.', $field, $length));
        }
    }

    private function validateMaxLength(string $field, mixed $value, int $length): void
    {
        // Guard this block so it only runs when the required condition is met.
        if (mb_strlen((string) $value) > $length) {
            $this->addError($field, sprintf('%s must not exceed %d characters.', $field, $length));
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
