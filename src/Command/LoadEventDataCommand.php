<?php

namespace App\Command;

use App\Entity\Evenement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-event-data',
    description: 'Seeds Evenement data with filieres and tags',
)]
class LoadEventDataCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Fetch a user to be the organizer (required relationship)
        $organizer = $this->entityManager->getRepository(User::class)->findOneBy([]);
        
        if (!$organizer) {
            $io->error('No user found to assign as organizer. Please create a user first.');
            return Command::FAILURE;
        }

        $eventsData = [
            [
                'titre' => 'Conférence IA & Data',
                'description' => 'Une conférence sur les dernières avancées en IA.',
                'lieu' => 'Paris',
                'type' => 'Conférence',
                'filieres' => ['ia', 'data', 'informatique'],
                'tags' => ['tech', 'innovation'],
                'dateDebut' => new \DateTime('+1 week'),
                'dateFin' => new \DateTime('+1 week 2 hours'),
            ],
            [
                'titre' => 'Atelier Développement Web',
                'description' => 'Apprenez à créer un site web moderne.',
                'lieu' => 'Lyon',
                'type' => 'Atelier',
                'filieres' => ['informatique', 'web'],
                'tags' => ['coding', 'frontend'],
                'dateDebut' => new \DateTime('+2 weeks'),
                'dateFin' => new \DateTime('+2 weeks 4 hours'),
            ],
            [
                'titre' => 'Séminaire Marketing Digital',
                'description' => 'Les stratégies gagnantes pour 2026.',
                'lieu' => 'Marseille',
                'type' => 'Séminaire',
                'filieres' => ['marketing', 'business'],
                'tags' => ['socialmedia', 'seo'],
                'dateDebut' => new \DateTime('+3 weeks'),
                'dateFin' => new \DateTime('+3 weeks 1 day'),
            ],
            [
                'titre' => 'Hackathon Cybersecurity',
                'description' => '24h pour sécuriser une infrastructure.',
                'lieu' => 'Bordeaux',
                'type' => 'Hackathon',
                'filieres' => ['informatique', 'cybersecurite'],
                'tags' => ['hacking', 'security'],
                'dateDebut' => new \DateTime('+1 month'),
                'dateFin' => new \DateTime('+1 month 2 days'),
            ],
        ];

        foreach ($eventsData as $data) {
            $event = new Evenement();
            $event->setTitre($data['titre']);
            $event->setDescription($data['description']);
            $event->setLieu($data['lieu']);
            $event->setType($data['type']);
            $event->setFilieres($data['filieres']);
            $event->setTags($data['tags']);
            $event->setDateDebut($data['dateDebut']);
            $event->setDateFin($data['dateFin']);
            $event->setOrganisateur($organizer);
            $event->setActif(true);
            $event->setNombrePlaces(100);

            $this->entityManager->persist($event);
        }

        $this->entityManager->flush();

        $io->success('Events seeded successfully with filieres and tags.');

        return Command::SUCCESS;
    }
}
