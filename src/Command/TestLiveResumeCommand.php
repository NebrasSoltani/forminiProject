<?php

namespace App\Command;

use App\Repository\EvenementRepository;
use App\Service\LiveResumeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-live-resume',
    description: 'Test generation of AI summary for a specific event',
)]
class TestLiveResumeCommand extends Command
{
    private EvenementRepository $evenementRepository;
    private LiveResumeService $resumeService;

    public function __construct(EvenementRepository $evenementRepository, LiveResumeService $resumeService)
    {
        parent::__construct();
        $this->evenementRepository = $evenementRepository;
        $this->resumeService = $resumeService;
    }

    protected function configure(): void
    {
        $this->addArgument('event_id', InputArgument::REQUIRED, 'ID of the event to test');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $eventId = $input->getArgument('event_id');

        $event = $this->evenementRepository->find($eventId);

        if (!$event) {
            $io->error(sprintf('Event with ID %s not found.', $eventId));
            return Command::FAILURE;
        }

        $io->title(sprintf('Generating summary for: %s', $event->getTitre()));
        
        $success = $this->resumeService->generateAndSaveResume($event);

        if ($success) {
            $io->success('Summary generated and saved successfully!');
            $io->info('Résumé: ' . $event->getResumeAuto());
        } else {
            $io->warning('Could not generate summary (check logs if Ollama is running). Local summary might have been set to a fallback.');
            $io->info('Résumé actuel: ' . $event->getResumeAuto());
        }

        return Command::SUCCESS;
    }
}
