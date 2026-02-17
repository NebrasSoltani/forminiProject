<?php

namespace App\Command;

use App\Service\SendGridEmailSender;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test-email',
    description: 'Test l\'envoi d\'email via SendGrid',
)]
class TestEmailCommand extends Command
{
    private SendGridEmailSender $emailSender;

    public function __construct(SendGridEmailSender $emailSender)
    {
        parent::__construct();
        $this->emailSender = $emailSender;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('🧪 Test d\'envoi d\'email SendGrid');
        $output->writeln('================================');

        try {
            $email = $this->emailSender->createEmail(
                'molkakam7@gmail.com',
                'Test Apprenant',
                'Test Email - Félicitations',
                '<h1>Bonjour!</h1><p>Ceci est un email de test de félicitation.</p>',
                'Bonjour! Ceci est un email de test de félicitation.'
            );

            $output->writeln('📧 Email créé avec succès');
            $output->writeln('  À: molkakam7@gmail.com');
            $output->writeln('  Sujet: Test Email - Félicitations');

            $output->writeln('📤 Envoi de l\'email...');
            $this->emailSender->send($email);

            $output->writeln('✅ Email envoyé avec succès!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('❌ Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
