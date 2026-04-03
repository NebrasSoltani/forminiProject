<?php

namespace App\Service;

use App\Entity\User;
use InvalidArgumentException;

class UserManager
{
    /**
     * Validates a user entity based on business rules
     *
     * @param User $user The user to validate
     * @return bool True if validation passes
     * @throws InvalidArgumentException If any business rule is violated
     */
    public function validate(User $user): bool
    {
        // Business Rule 1: The user name is required
        $this->validateName($user);
        
        // Business Rule 2: The email must be valid
        $this->validateEmail($user);
        
        // Business Rule 3: The password must contain at least 8 characters
        $this->validatePassword($user);
        
        // Business Rule 4: The role must be defined
        $this->validateRole($user);
        
        return true;
    }
    
    /**
     * Validates that the user has a name (nom + prenom)
     *
     * @param User $user
     * @throws InvalidArgumentException If name is missing
     */
    private function validateName(User $user): void
    {
        $nom = trim($user->getNom() ?? '');
        $prenom = trim($user->getPrenom() ?? '');
        
        if (empty($nom) || empty($prenom)) {
            throw new InvalidArgumentException('Le nom et le prénom sont obligatoires');
        }
    }
    
    /**
     * Validates that the email is in correct format
     *
     * @param User $user
     * @throws InvalidArgumentException If email is invalid
     */
    private function validateEmail(User $user): void
    {
        $email = trim($user->getEmail() ?? '');
        
        if (empty($email)) {
            throw new InvalidArgumentException('L\'email est obligatoire');
        }
        
        // Check length first
        if (strlen($email) > 180) {
            throw new InvalidArgumentException('L\'email ne peut pas dépasser 180 caractères');
        }
        
        // Basic email validation using filter_var
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('L\'email n\'est pas valide');
        }
    }
    
    /**
     * Validates that the password has at least 8 characters
     *
     * @param User $user
     * @throws InvalidArgumentException If password is too short
     */
    private function validatePassword(User $user): void
    {
        $password = $user->getPassword() ?? '';
        
        if (empty($password)) {
            throw new InvalidArgumentException('Le mot de passe est obligatoire');
        }
        
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères');
        }
    }
    
    /**
     * Validates that the role is defined and valid
     *
     * @param User $user
     * @throws InvalidArgumentException If role is invalid
     */
    private function validateRole(User $user): void
    {
        $role = $user->getRoleUtilisateur();
        
        if (empty($role)) {
            throw new InvalidArgumentException('Le rôle utilisateur est obligatoire');
        }
        
        $validRoles = ['formateur', 'apprenant', 'societe', 'admin'];
        
        if (!in_array($role, $validRoles)) {
            throw new InvalidArgumentException('Le rôle doit être l\'un des suivants: ' . implode(', ', $validRoles));
        }
    }
    
    /**
     * Additional validation method for phone number (optional)
     *
     * @param User $user
     * @throws InvalidArgumentException If phone is invalid
     */
    public function validatePhone(User $user): void
    {
        $phone = trim($user->getTelephone() ?? '');
        
        if (empty($phone)) {
            throw new InvalidArgumentException('Le téléphone est obligatoire');
        }
        
        // Basic phone validation for Tunisia (adjust as needed)
        if (!preg_match('/^[2-9]\d{7}$/', $phone)) {
            throw new InvalidArgumentException('Le numéro de téléphone n\'est pas valide');
        }
    }
    
    /**
     * Complete validation including all required fields
     *
     * @param User $user
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validateComplete(User $user): bool
    {
        $this->validate($user);
        $this->validatePhone($user);
        
        // Validate birth date
        $dateNaissance = $user->getDateNaissance();
        if (!$dateNaissance) {
            throw new InvalidArgumentException('La date de naissance est obligatoire');
        }
        
        if ($dateNaissance >= new \DateTime()) {
            throw new InvalidArgumentException('La date de naissance doit être dans le passé');
        }
        
        return true;
    }
}
