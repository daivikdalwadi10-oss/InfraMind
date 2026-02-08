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
     * Add a custom validation error.
     */
    public function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
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

        if (isset($data['description']) && $data['description'] !== '') {
            $validator->length('description', $data['description'], 0, 2000);
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

    public static function validateManagerCreate(array $data): void
    {
        $validator = new Validator();

        $validator->required('title', $data['title'] ?? null);
        if (isset($data['title'])) {
            $validator->length('title', $data['title'], 1, 500);
        }

        $validator->required('analysisType', $data['analysisType'] ?? null);
        if (isset($data['analysisType'])) {
            $validator->enum('analysisType', $data['analysisType'], ['LATENCY', 'SECURITY', 'OUTAGE', 'CAPACITY']);
        }

        $validator->required('assignedTo', $data['assignedTo'] ?? null);
        if (isset($data['assignedTo'])) {
            $validator->uuid('assignedTo', $data['assignedTo']);
        }

        if (isset($data['teamId'])) {
            $validator->uuid('teamId', $data['teamId']);
        }

        if (isset($data['taskDescription'])) {
            $validator->length('taskDescription', $data['taskDescription'], 0, 2000);
        }

        $validator->throwIfErrors();
    }

    public static function validateUpdate(array $data): void
    {
        $validator = new Validator();

        if (isset($data['symptoms'])) {
            if (!is_array($data['symptoms'])) {
                $validator->addError('symptoms', 'Symptoms must be an array');
            }
        }

        if (isset($data['signals'])) {
            if (!is_array($data['signals'])) {
                $validator->addError('signals', 'Signals must be an array');
            }
        }

        if (isset($data['hypotheses'])) {
            if (!is_array($data['hypotheses'])) {
                $validator->addError('hypotheses', 'Hypotheses must be an array');
            }
        }

        if (isset($data['environmentContext']) && !is_array($data['environmentContext'])) {
            $validator->addError('environmentContext', 'Environment context must be an object');
        }

        if (isset($data['timelineEvents']) && !is_array($data['timelineEvents'])) {
            $validator->addError('timelineEvents', 'Timeline events must be an object');
        }

        if (isset($data['dependencyImpact']) && !is_array($data['dependencyImpact'])) {
            $validator->addError('dependencyImpact', 'Dependency impact must be an object');
        }

        if (isset($data['riskClassification']) && !is_array($data['riskClassification'])) {
            $validator->addError('riskClassification', 'Risk classification must be an object');
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

/**
 * Incident validation.
 */
class IncidentValidator
{
    public static function validateCreate(array $data): void
    {
        $validator = new Validator();

        $validator->required('title', $data['title'] ?? null);
        if (isset($data['title'])) {
            $validator->length('title', $data['title'], 1, 500);
        }

        if (isset($data['description']) && $data['description'] !== '') {
            $validator->length('description', $data['description'], 0, 2000);
        }

        if (isset($data['severity'])) {
            $validator->enum('severity', $data['severity'], ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']);
        }

        if (isset($data['assignedTo'])) {
            $validator->uuid('assignedTo', $data['assignedTo']);
        }

        $validator->throwIfErrors();
    }

    public static function validateUpdate(array $data): void
    {
        $validator = new Validator();

        if (isset($data['title'])) {
            $validator->length('title', $data['title'], 1, 500);
        }

        if (isset($data['description']) && $data['description'] !== '') {
            $validator->length('description', $data['description'], 0, 2000);
        }

        if (isset($data['severity'])) {
            $validator->enum('severity', $data['severity'], ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']);
        }

        if (isset($data['status'])) {
            $validator->enum('status', $data['status'], ['OPEN', 'INVESTIGATING', 'RESOLVED']);
        }

        if (isset($data['assignedTo'])) {
            $validator->uuid('assignedTo', $data['assignedTo']);
        }

        $validator->throwIfErrors();
    }
}

/**
 * Team validation.
 */
class TeamValidator
{
    public static function validateCreate(array $data): void
    {
        $validator = new Validator();

        $validator->required('name', $data['name'] ?? null);
        if (isset($data['name'])) {
            $validator->length('name', $data['name'], 2, 100);
        }

        if (isset($data['description'])) {
            $validator->length('description', $data['description'], 0, 1000);
        }

        $validator->throwIfErrors();
    }

    public static function validateMember(array $data): void
    {
        $validator = new Validator();
        $validator->required('userId', $data['userId'] ?? null);
        if (isset($data['userId'])) {
            $validator->uuid('userId', $data['userId']);
        }
        $validator->throwIfErrors();
    }
}

/**
 * AI output validation.
 */
class AiOutputValidator
{
    public static function validateUpdate(array $data): void
    {
        $validator = new Validator();
        $validator->required('status', $data['status'] ?? null);
        if (isset($data['status'])) {
            $validator->enum('status', $data['status'], ['GENERATED', 'ACCEPTED', 'REJECTED', 'EDITED']);
        }

        if (isset($data['payload']) && !is_array($data['payload'])) {
            $validator->addError('payload', 'Payload must be an object');
        }

        $validator->throwIfErrors();
    }
}

/**
 * Infrastructure state validation.
 */
class InfrastructureStateValidator
{
    public static function validateCreate(array $data): void
    {
        $validator = new Validator();

        $validator->required('component', $data['component'] ?? null);
        if (isset($data['component'])) {
            $validator->length('component', $data['component'], 1, 255);
        }

        if (isset($data['status'])) {
            $validator->enum('status', $data['status'], ['HEALTHY', 'DEGRADED', 'OUTAGE', 'MAINTENANCE']);
        }

        if (isset($data['summary']) && $data['summary'] !== '') {
            $validator->length('summary', $data['summary'], 0, 2000);
        }

        $validator->throwIfErrors();
    }

    public static function validateUpdate(array $data): void
    {
        $validator = new Validator();

        if (isset($data['component'])) {
            $validator->length('component', $data['component'], 1, 255);
        }

        if (isset($data['status'])) {
            $validator->enum('status', $data['status'], ['HEALTHY', 'DEGRADED', 'OUTAGE', 'MAINTENANCE']);
        }

        if (isset($data['summary']) && $data['summary'] !== '') {
            $validator->length('summary', $data['summary'], 0, 2000);
        }

        $validator->throwIfErrors();
    }
}

/**
 * Architecture risk validation.
 */
class ArchitectureRiskValidator
{
    public static function validateCreate(array $data): void
    {
        $validator = new Validator();

        $validator->required('title', $data['title'] ?? null);
        if (isset($data['title'])) {
            $validator->length('title', $data['title'], 1, 500);
        }

        if (isset($data['description']) && $data['description'] !== '') {
            $validator->length('description', $data['description'], 0, 2000);
        }

        if (isset($data['severity'])) {
            $validator->enum('severity', $data['severity'], ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']);
        }

        if (isset($data['status'])) {
            $validator->enum('status', $data['status'], ['OPEN', 'MITIGATING', 'RESOLVED']);
        }

        if (isset($data['analysisId'])) {
            $validator->uuid('analysisId', $data['analysisId']);
        }

        $validator->throwIfErrors();
    }

    public static function validateUpdate(array $data): void
    {
        $validator = new Validator();

        if (isset($data['title'])) {
            $validator->length('title', $data['title'], 1, 500);
        }

        if (isset($data['description']) && $data['description'] !== '') {
            $validator->length('description', $data['description'], 0, 2000);
        }

        if (isset($data['severity'])) {
            $validator->enum('severity', $data['severity'], ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']);
        }

        if (isset($data['status'])) {
            $validator->enum('status', $data['status'], ['OPEN', 'MITIGATING', 'RESOLVED']);
        }

        if (isset($data['analysisId'])) {
            $validator->uuid('analysisId', $data['analysisId']);
        }

        $validator->throwIfErrors();
    }
}

/**
 * Meeting validation.
 */
class MeetingValidator
{
    public static function validateCreate(array $data): void
    {
        $validator = new Validator();

        $validator->required('title', $data['title'] ?? null);
        if (isset($data['title'])) {
            $validator->length('title', $data['title'], 1, 500);
        }

        $validator->required('scheduledAt', $data['scheduledAt'] ?? null);

        if (isset($data['agenda']) && $data['agenda'] !== '') {
            $validator->length('agenda', $data['agenda'], 0, 2000);
        }

        if (isset($data['status'])) {
            $validator->enum('status', $data['status'], ['SCHEDULED', 'COMPLETED', 'CANCELLED']);
        }

        if (isset($data['durationMinutes'])) {
            $validator->intRange('durationMinutes', (int) $data['durationMinutes'], 15, 480);
        }

        if (isset($data['analysisId'])) {
            $validator->uuid('analysisId', $data['analysisId']);
        }

        if (isset($data['incidentId'])) {
            $validator->uuid('incidentId', $data['incidentId']);
        }

        $validator->throwIfErrors();
    }

    public static function validateUpdate(array $data): void
    {
        $validator = new Validator();

        if (isset($data['title'])) {
            $validator->length('title', $data['title'], 1, 500);
        }

        if (isset($data['agenda']) && $data['agenda'] !== '') {
            $validator->length('agenda', $data['agenda'], 0, 2000);
        }

        if (isset($data['status'])) {
            $validator->enum('status', $data['status'], ['SCHEDULED', 'COMPLETED', 'CANCELLED']);
        }

        if (isset($data['durationMinutes'])) {
            $validator->intRange('durationMinutes', (int) $data['durationMinutes'], 15, 480);
        }

        if (isset($data['analysisId'])) {
            $validator->uuid('analysisId', $data['analysisId']);
        }

        if (isset($data['incidentId'])) {
            $validator->uuid('incidentId', $data['incidentId']);
        }

        $validator->throwIfErrors();
    }
}
