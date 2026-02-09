<?php

declare(strict_types=1);

namespace InfraMind\Tests;

use InfraMind\Exceptions\ValidationException;
use InfraMind\Validators\SignupValidator;
use InfraMind\Validators\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testValidatePasswordAcceptsStrongPassword(): void
    {
        $validator = new Validator();
        $result = $validator->validatePassword('StrongPass123!');

        $this->assertTrue($result);
        $this->assertFalse($validator->hasErrors());
    }

    public function testValidatePasswordRejectsWeakPassword(): void
    {
        $validator = new Validator();
        $result = $validator->validatePassword('weak');

        $this->assertFalse($result);
        $this->assertTrue($validator->hasErrors());
    }

    public function testSignupValidatorAcceptsValidData(): void
    {
        SignupValidator::validate(
            [
                'email' => 'valid@example.com',
                'password' => 'StrongPass123!',
                'displayName' => 'Valid User',
                'role' => 'EMPLOYEE',
            ]
        );

        $this->assertTrue(true);
    }

    public function testSignupValidatorRejectsInvalidRole(): void
    {
        $this->expectException(ValidationException::class);

        SignupValidator::validate(
            [
                'email' => 'valid@example.com',
                'password' => 'StrongPass123!',
                'displayName' => 'Valid User',
                'role' => 'DEVELOPER',
            ]
        );
    }
}
