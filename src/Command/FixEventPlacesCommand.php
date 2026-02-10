<?php

namespace App\Command;

use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-event-places',
    description: 'Adjusts event places by subtracting current participants (One-time migration)',
)]
class FixEventPlacesCommand extends Command
{
    public function __construct(
        private EvenementRepository $evenementRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $events = $this->evenementRepository->findAll();
        $count = 0;

        foreach ($events as $event) {
            $participants = $event->getParticipations()->count();
            if ($participants > 0 && $event->getNombrePlaces() > 0) {
                // Assuming current nombrePlaces is 'Capacity', we subtract participants to get 'Remaining'
                // This should only be run ONCE when switching logic.
                // However, without knowing if it was already decremented, this is risky.
                // The user's specific case: DB=300, Participants=1, Display=299.
                // So we want DB to become 299.
                
                // We will assume that if (places + participants) seems like a round number (capacity), maybe?
                // Or just apply the user's logic: We want DB value to match what was displayed "Remaining".
                
                // If the logic was "Remaining = Capacity - Participants", and now "Remaining = DB Value",
                // Then New_DB_Value should be Old_DB_Value - Participants.
                
                $newPlaces = $event->getNombrePlaces() - $participants;
                if ($newPlaces < 0) $newPlaces = 0;
                
                $event->setNombrePlaces($newPlaces);
                $count++;
            }
        }

        $this->entityManager->flush();
        $io->success("$count events updated to reflect remaining places in database.");

        return Command::SUCCESS;
    }
}
