<?php

namespace App\Command;

use App\Entity\User;
use App\Enum\Gouvernorat;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates an admin user with specified credentials'
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if admin already exists
        $existingAdmin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'Admin@gmail.com']);
        
        if ($existingAdmin) {
            $io->warning('Admin user with email Admin@gmail.com already exists!');
            return Command::FAILURE;
        }

        // Create new admin user
        $admin = new User();
        
        // Set basic information
        $admin->setEmail('Admin@gmail.com');
        $admin->setNom('Admin');
        $admin->setPrenom('System');
        $admin->setTelephone('00000000');
        $admin->setDateNaissance(new \DateTime('1990-01-01'));
        $admin->setRoleUtilisateur('admin');
        
        // Set required fields with default values
        $admin->setGouvernorat(Gouvernorat::TUNIS);
        $admin->setProfession('Administrator');
        $admin->setNiveauEtude('Higher Education');
        
        // Set roles
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        
        // Hash and set password
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'Admin#123');
        $admin->setPassword($hashedPassword);
        
        // Set email as verified
        $admin->setIsEmailVerified(true);
        $admin->setEmailVerifiedAt(new \DateTime());

        // Save to database
        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success('Admin user created successfully!');
        $io->table(
            ['Field', 'Value'],
            [
                ['Email', 'Admin@gmail.com'],
                ['Password', 'Admin#123'],
                ['Roles', 'ROLE_ADMIN, ROLE_USER'],
                ['Name', 'Admin System'],
                ['User Type', 'admin'],
                ['Email Verified', 'Yes']
            ]
        );

        return Command::SUCCESS;
    }
}
