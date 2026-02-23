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
    name: 'app:seed-360-events',
    description: 'Seeds Evenement data with specialized 360 views',
)]
class Load360EventDataCommand extends Command
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

        $organizer = $this->entityManager->getRepository(User::class)->findOneBy([]);
        
        if (!$organizer) {
            $io->error('No user found to assign as organizer.');
            return Command::FAILURE;
        }

        $eventsData = [
            [
                'titre' => 'Futur City Plaza 360',
                'description' => 'Explorez la place futuriste en immersion totale.',
                'lieu' => 'Neo-Tokyo',
                'type' => 'Conférence',
                'image360' => null, // User will upload
                'urlStreetView' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3240.8280303808788!2d139.77039831525515!3d35.6991572799309!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x60188c1da27c9577%3A0x6d5952d9a3b2b8d!2sAkihabara+Station!5e0!3m2!1sen!2sjp!4v1565154374351',
            ],
            [
                'titre' => 'Creative Balloons Exhibition',
                'description' => 'Une galerie d\'art immersive avec des sphères lumineuses.',
                'lieu' => 'Paris Art Center',
                'type' => 'Exposition',
                'image360' => 'https://pannellum.org/images/alma.jpg', // Placeholder image URL works too with Pannellum but ideally local filename
                'urlStreetView' => null,
            ],
        ];

        foreach ($eventsData as $data) {
            $event = new Evenement();
            $event->setTitre($data['titre']);
            $event->setDescription($data['description']);
            $event->setLieu($data['lieu']);
            $event->setType($data['type']);
            $event->setOrganisateur($organizer);
            $event->setIsActif(true);
            $event->setNombrePlaces(200);
            $event->setDateDebut(new \DateTime('+2 days'));
            $event->setDateFin(new \DateTime('+2 days 4 hours'));
            
            // For the equirectangular one, we use a remote URL as placeholder if we don't have the file
            // But image360 usually expects a filename. For testing, we can put the full URL 
            // and the JS will handle it because it treats both valid.
            $event->setImage360($data['image360']);
            $event->setUrlStreetView($data['urlStreetView']);

            $this->entityManager->persist($event);
        }

        $this->entityManager->flush();

        $io->success('360 Events seeded successfully.');

        return Command::SUCCESS;
    }
}
