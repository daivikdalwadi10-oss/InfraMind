<?php

declare(strict_types=1);

namespace InfraMind\Validators;

use InfraMind\Exceptions\ValidationException;

/**
 * Input validation utility with comprehensive rules.
 */
class Validator
{
    private array $errors = [];

    /**
     * Validate email format.
     */
    public function validateEmail(string $email): bool
    {
        $valid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        if (!$valid) {
            $this->errors['email'] = 'Invalid email format';
        }
        return $valid;
    }

    /**
     * Validate password strength.
     */
    public function validatePassword(string $password): bool
    {
        // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special char
        if (strlen($password) < 8) {
            $this->errors['password'] = 'Password must be at least 8 characters';
            return false;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $this->errors['password'] = 'Password must contain uppercase letter';
            return false;
        }

        if (!preg_match('/[a-z]/', $password)) {
            $this->errors['password'] = 'Password must contain lowercase letter';
            return false;
        }

        if (!preg_match('/[0-9]/', $password)) {
            $this->errors['password'] = 'Password must contain number';
            return false;
        }

        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/]/', $password)) {
            $this->errors['password'] = 'Password must contain special character';
            return false;
        }

        return true;
    }

    /**
     * Validate required field.
     */
    public function required(string $field, mixed $value): bool
    {
        if (empty($value) && $value !== '0' && $value !== 0 && $value !== false) {
            $this->errors[$field] = "The $field field is required";
            return false;
        }
        return true;
    }

    /**
     * Validate string length.
     */
    public function length(string $field, string $value, int $min, int $max): bool
    {
        $length = strlen($value);
        if ($length < $min || $length > $max) {
            $this->errors[$field] = "The $field must be between $min and $max characters";
            return false;
        }
        return true;
    }

    /**
     * Validate UUID format.
     */
    public function uuid(string $field, string $value): bool
    {
        $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        if (!preg_match($uuidPattern, $value)) {
            $this->errors[$field] = "The $field must be a valid UUID";
            return false;
        }
        return true;
    }

    /**
     * Validate enum value.
     */
    public function enum(string $field, mixed $value, array $allowedValues): bool
    {
        if (!in_array($value, $allowedValues, true)) {
            $this->errors[$field] = "The $field must be one of: " . implode(', ', $allowedValues);
            return false;
        }
        return true;
    }

    /**
     * Validate integer range.
     */
    public function intRange(string $field, int $value, int $min, int $max): bool
    {
        if ($value < $min || $value > $max) {
            $this->errors[$field] = "The $field must be between $min and $max";
            return false;
        }
        return true;
    }

    /**
     * Get validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Throw validation exception if there are errors.
     */
    public function throwIfErrors(): void
    {
        if ($this->hasErrors()) {
            throw new ValidationException($this->errors, 'Validation failed');
        }
    }
}

/**
 * Signup validation.
 */
class SignupValidator
{
    public static function validate(array $data): void
    {
        $validator = new Validator();

        $validator->required('email', $data['email'] ?? null);
        if (isset($data['email'])) {
            $validator->validateEmail($data['email']);
        }

        $validator->required('password', $data['password'] ?? null);
        if (isset($data['password'])) {
            $validator->validatePassword($data['password']);
        }

        $validator->required('displayName', $data['displayName'] ?? null);
        if (isset($data['displayName'])) {
            $validator->length('displayName', $data['displayName'], 1, 255);
        }

        $validator->required('role', $data['role'] ?? null);
        if (isset($data['role'])) {
            $validator->enum('role', $data['role'], ['EMPLOYEE', 'MANAGER', 'OWNER']);
        }

        $validator->throwIfErrors();
    }
}

/**
 * Login validation.
 */
class LoginValidator
{
    public static function validate(array $data): void
    {
        $validator = new Validator();

        $validator->required('email', $data['email'] ?? null);
        if (isset($data['email'])) {
            $validator->validateEmail($data['email']);
        }

        $validator->required('password', $data['password'] ?? null);

        $validator->throwIfErrors();
    }
}

/**
 * Task validation.
 */
class TaskValidator
{
    public static function validateCreate(array $data): void
    {
        $validator = new Validator();

        $validator->required('title', $data['title'] ?? null);
        if (isset($data['title'])) {
            $validator->length('title', $data['title'], 1, 255);
        }

        $validator->required('description', $data['description'] ?? null);
        if (isset($data['description'])) {
            $validator->length('description', $data['description'], 1, 2000);
        }

        if (isset($data['assignedTo'])) {
            $validator->uuid('assignedTo', $data['assignedTo']);
        }

        $validator->throwIfErrors();
    }
}

/**
 * Analysis validation.
 */
class AnalysisValidator
{
    public static function validateCreate(array $data): void
    {
        $validator = new Validator();

        $validator->required('taskId', $data['taskId'] ?? null);
        if (isset($data['taskId'])) {
            $validator->uuid('taskId', $data['taskId']);
        }

        $validator->required('analysisType', $data['analysisType'] ?? null);
        if (isset($data['analysisType'])) {
            $validator->enum('analysisType', $data['analysisType'], ['LATENCY', 'SECURITY', 'OUTAGE', 'CAPACITY']);
        }

        $validator->throwIfErrors();
    }

    public static function validateUpdate(array $data): void
    {
        $validator = new Validator();

        if (isset($data['symptoms'])) {
            if (!is_array($data['symptoms'])) {
                $validator->errors['symptoms'] = 'Symptoms must be an array';
            }
        }

        if (isset($data['signals'])) {
            if (!is_array($data['signals'])) {
                $validator->errors['signals'] = 'Signals must be an array';
            }
        }

        if (isset($data['hypotheses'])) {
            if (!is_array($data['hypotheses'])) {
                $validator->errors['hypotheses'] = 'Hypotheses must be an array';
            }
        }

        if (isset($data['readinessScore'])) {
            $validator->intRange('readinessScore', (int) $data['readinessScore'], 0, 100);
        }

        $validator->throwIfErrors();
    }

    public static function validateSubmit(array $data): void
    {
        $validator = new Validator();
        $validator->throwIfErrors();
    }

    public static function validateReview(array $data): void
    {
        $validator = new Validator();

        $validator->required('decision', $data['decision'] ?? null);
        if (isset($data['decision'])) {
            $validator->enum('decision', $data['decision'], ['APPROVE', 'REJECT']);
        }

        if (isset($data['feedback'])) {
            $validator->length('feedback', $data['feedback'], 0, 2000);
        }

        $validator->throwIfErrors();
    }
}
