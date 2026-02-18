<?php

namespace App\Command;

use App\Service\SendGridEmailSender;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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

    protected function configure(): void
    {
        $this->addArgument('recipient', InputArgument::REQUIRED, 'Email recipient address');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $recipient = $input->getArgument('recipient');

        $io->title('Test Email - Template Félicitation');
        $io->text("Envoi du template de félicitation à: $recipient");

        // Données d'exemple pour le template
        $prenom = 'molka';
        $nomQuiz = 'Symfony';
        $score = 100;
        $bonnesReponses = 1;
        $totalQuestions = 1;

        try {
            $htmlContent = $this->creerTemplateEmailFelicitation(
                $prenom,
                $nomQuiz,
                $score,
                $bonnesReponses,
                $totalQuestions
            );

            $email = $this->emailSender->createEmail(
                $recipient,
                $prenom,
                "Félicitations ! Vous avez réussi le quiz « {$nomQuiz} »",
                $htmlContent,
                "Félicitations {$prenom} ! Vous avez réussi le quiz {$nomQuiz} avec {$score}% ({$bonnesReponses}/{$totalQuestions} bonnes réponses)."
            );

            $this->emailSender->send($email);

            $io->success('Email de félicitation envoyé avec succès ! Vérifiez votre boîte de réception.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Échec de l\'envoi: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function creerTemplateEmailFelicitation(
        string $prenom,
        string $nomQuiz,
        float $score,
        int $bonnesReponses,
        int $totalQuestions
    ): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .content {
            padding: 40px 20px;
        }
        .greeting {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
        }
        .score-box {
            background-color: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .score-value {
            font-size: 40px;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        .quiz-info {
            margin: 20px 0;
            color: #666;
        }
        .quiz-info p {
            margin: 10px 0;
            line-height: 1.6;
        }
        .congratulations {
            background-color: #e8f5e9;
            border: 1px solid #4caf50;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            color: #2e7d32;
            text-align: center;
            font-weight: bold;
        }
        .footer {
            background-color: #f5f5f5;
            padding: 20px;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Félicitations !</h1>
        </div>

        <div class="content">
            <div class="greeting">
                Bonjour {$prenom},
            </div>

            <p>Nous sommes heureux de vous annoncer que vous avez réussi le quiz avec un excellent score !</p>

            <div class="score-box">
                <p style="margin-top: 0; color: #667eea;">Résultats du quiz</p>
                <p style="margin: 10px 0;"><strong>{$nomQuiz}</strong></p>
                <div class="score-value">{$score}%</div>
                <p style="margin: 10px 0; color: #666;">{$bonnesReponses} bonnes réponses sur {$totalQuestions} questions</p>
            </div>

            <div class="congratulations">
                Vous avez dépassé la barre des 80% ! Continuez sur cette lancée ! 🚀
            </div>

            <div class="quiz-info">
                <p><strong>Qu'est-ce que cela signifie ?</strong></p>
                <p>Vous maîtrisez très bien les concepts de ce module. Vous pouvez avancer avec confiance vers les modules suivants.</p>

                <p><strong>Prochaines étapes :</strong></p>
                <ul>
                    <li>Explorez le contenu avancé du module suivant</li>
                    <li>Revisitez les concepts pour approfondir votre compréhension</li>
                    <li>Aidez vos collègues apprenants si possible</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé par le système d'apprentissage FORMINI.</p>
            <p>Si vous avez des questions, veuillez contacter votre formateur.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
