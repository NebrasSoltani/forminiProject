<?php

namespace App\Command;

use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-product-translations',
    description: 'Create sample translations for existing products'
)]
class CreateProductTranslationsCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Création des traductions de produits');
        
        $produitRepository = $this->em->getRepository(Produit::class);
        $produits = $produitRepository->findAll();
        
        $count = 0;
        
        foreach ($produits as $produit) {
            // Définir la locale française pour récupérer les valeurs
            $produit->setTranslatableLocale('fr');
            $this->em->refresh($produit);
            
            $nomFr = $produit->getNom();
            $descriptionFr = $produit->getDescription();
            
            if ($nomFr && $descriptionFr) {
                // Créer traduction anglaise
                $produit->setTranslatableLocale('en');
                $produit->setNom($nomFr . ' (EN)');
                $produit->setDescription($descriptionFr . ' - English version');
                $this->em->persist($produit);
                
                $count++;
                
                if ($count % 5 === 0) {
                    $this->em->flush();
                    $io->text("Traité : {$count} produits...");
                }
            }
        }
        
        $this->em->flush();
        
        $io->success("Terminé : {$count} traductions anglaises créées");
        
        return Command::SUCCESS;
    }
}
