<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    private UserManager $userManager;

    protected function setUp(): void
    {
        $this->userManager = new UserManager();
    }

    /**
     * Test 1: Valid User
     * Check that validation returns true when all data is correct.
     */
    public function testValidUser(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('password123');
        $user->setRoleUtilisateur('apprenant');

        $result = $this->userManager->validate($user);

        $this->assertTrue($result, 'Valid user should pass validation');
    }

    /**
     * Test 2: Missing Name
     * Expect an exception when the name is empty.
     */
    public function testMissingName(): void
    {
        $user = new User();
        $user->setNom('');  // Empty name
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('password123');
        $user->setRoleUtilisateur('apprenant');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom et le prénom sont obligatoires');

        $this->userManager->validate($user);
    }

    /**
     * Test 3: Missing First Name
     * Expect an exception when the first name is empty.
     */
    public function testMissingFirstName(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('');  // Empty first name
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('password123');
        $user->setRoleUtilisateur('apprenant');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom et le prénom sont obligatoires');

        $this->userManager->validate($user);
    }

    /**
     * Test 4: Invalid Email
     * Expect an exception when the email format is incorrect.
     */
    public function testInvalidEmail(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('invalid-email');  // Invalid email format
        $user->setPassword('password123');
        $user->setRoleUtilisateur('apprenant');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email n\'est pas valide');

        $this->userManager->validate($user);
    }

    /**
     * Test 5: Empty Email
     * Expect an exception when the email is empty.
     */
    public function testEmptyEmail(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('');  // Empty email
        $user->setPassword('password123');
        $user->setRoleUtilisateur('apprenant');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email est obligatoire');

        $this->userManager->validate($user);
    }

    /**
     * Test 6: Email Too Long
     * Expect an exception when the email exceeds 180 characters.
     */
    public function testEmailTooLong(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        
        // Create an email longer than 180 characters
        $longEmail = str_repeat('a', 170) . '@example.com';  // This will be > 180 chars
        $user->setEmail($longEmail);
        $user->setPassword('password123');
        $user->setRoleUtilisateur('apprenant');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email ne peut pas dépasser 180 caractères');

        $this->userManager->validate($user);
    }

    /**
     * Test 7: Short Password
     * Expect an exception when the password has fewer than 8 characters.
     */
    public function testShortPassword(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('short');  // Only 5 characters
        $user->setRoleUtilisateur('apprenant');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir au moins 8 caractères');

        $this->userManager->validate($user);
    }

    /**
     * Test 8: Empty Password
     * Expect an exception when the password is empty.
     */
    public function testEmptyPassword(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('');  // Empty password
        $user->setRoleUtilisateur('apprenant');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe est obligatoire');

        $this->userManager->validate($user);
    }

    /**
     * Test 9: Missing Role
     * Expect an exception when the role is not defined.
     */
    public function testMissingRole(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('password123');
        // Role not set

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le rôle utilisateur est obligatoire');

        $this->userManager->validate($user);
    }

    /**
     * Test 10: Invalid Role
     * Expect an exception when the role is not in the allowed list.
     */
    public function testInvalidRole(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('password123');
        $user->setRoleUtilisateur('invalid_role');  // Not in allowed roles

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le rôle doit être l\'un des suivants:');

        $this->userManager->validate($user);
    }

    /**
     * Test 11: Valid Role - Formateur
     * Test that all valid roles pass validation.
     */
    public function testValidRoleFormateur(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('password123');
        $user->setRoleUtilisateur('formateur');

        $result = $this->userManager->validate($user);

        $this->assertTrue($result, 'Formateur role should be valid');
    }

    /**
     * Test 12: Valid Role - Admin
     * Test that admin role passes validation.
     */
    public function testValidRoleAdmin(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('password123');
        $user->setRoleUtilisateur('admin');

        $result = $this->userManager->validate($user);

        $this->assertTrue($result, 'Admin role should be valid');
    }

    /**
     * Test 13: Valid Role - Societe
     * Test that societe role passes validation.
     */
    public function testValidRoleSociete(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('password123');
        $user->setRoleUtilisateur('societe');

        $result = $this->userManager->validate($user);

        $this->assertTrue($result, 'Societe role should be valid');
    }

    /**
     * Test 14: Whitespace Handling
     * Test that whitespace in names and email is handled correctly.
     */
    public function testWhitespaceHandling(): void
    {
        $user = new User();
        $user->setNom('  Dupont  ');  // Names with whitespace
        $user->setPrenom('  Jean  ');
        $user->setEmail('jean.dupont @example.com');  // Email with space in middle
        $user->setPassword('password123');
        $user->setRoleUtilisateur('apprenant');

        // The email with space in the middle should fail validation
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email n\'est pas valide');

        $this->userManager->validate($user);
    }

    /**
     * Test 15: Complete Validation with Phone
     * Test the complete validation method including phone.
     */
    public function testCompleteValidationWithValidPhone(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('password123');
        $user->setRoleUtilisateur('apprenant');
        $user->setTelephone('23456789');  // Valid Tunisian phone number
        $user->setDateNaissance(new \DateTime('1990-01-01'));

        $result = $this->userManager->validateComplete($user);

        $this->assertTrue($result, 'Complete validation should pass with valid data');
    }

    /**
     * Test 16: Invalid Phone Number
     * Test that invalid phone numbers are rejected.
     */
    public function testInvalidPhoneNumber(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('password123');
        $user->setRoleUtilisateur('apprenant');
        $user->setTelephone('12345678');  // Invalid Tunisian phone (starts with 1)
        $user->setDateNaissance(new \DateTime('1990-01-01'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le numéro de téléphone n\'est pas valide');

        $this->userManager->validateComplete($user);
    }

    /**
     * Test 17: Missing Birth Date
     * Test that missing birth date is rejected in complete validation.
     */
    public function testMissingBirthDate(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('password123');
        $user->setRoleUtilisateur('apprenant');
        $user->setTelephone('23456789');
        // Birth date not set

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de naissance est obligatoire');

        $this->userManager->validateComplete($user);
    }

    /**
     * Test 18: Future Birth Date
     * Test that future birth dates are rejected.
     */
    public function testFutureBirthDate(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('password123');
        $user->setRoleUtilisateur('apprenant');
        $user->setTelephone('23456789');
        $user->setDateNaissance(new \DateTime('tomorrow'));  // Future date

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de naissance doit être dans le passé');

        $this->userManager->validateComplete($user);
    }

    /**
     * Test 19: Edge Case - Exactly 8 Character Password
     * Test that exactly 8 character passwords are accepted.
     */
    public function testExactlyEightCharacterPassword(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('pass1234');  // Exactly 8 characters
        $user->setRoleUtilisateur('apprenant');

        $result = $this->userManager->validate($user);

        $this->assertTrue($result, 'Exactly 8 character password should be valid');
    }

    /**
     * Test 20: Edge Case - Null Values
     * Test that null values are handled correctly.
     */
    public function testNullValues(): void
    {
        $user = new User();
        $user->setNom(null);
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('password123');
        $user->setRoleUtilisateur('apprenant');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom et le prénom sont obligatoires');

        $this->userManager->validate($user);
    }
}
