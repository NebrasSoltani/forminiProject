<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\FormationRepository;
use App\Repository\InscriptionRepository;
use App\Repository\CommandeRepository;
use App\Repository\EvenementRepository;  // ← AJOUTEZ CETTE LIGNE
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(
        UserRepository $userRepository,
        FormationRepository $formationRepository,
        InscriptionRepository $inscriptionRepository,
        CommandeRepository $commandeRepository,
        EvenementRepository $evenementRepository  // ← AJOUTEZ CETTE LIGNE
    ): Response {
        // Statistiques globales
        $totalUsers = $userRepository->count([]);
        $totalFormateurs = $userRepository->count(['roleUtilisateur' => 'formateur']);
        $totalApprenants = $userRepository->count(['roleUtilisateur' => 'apprenant']);
        $totalAdmins = $userRepository->count(['roles' => json_encode(['ROLE_ADMIN'])]);

        $totalFormations = $formationRepository->count([]);
        $formationsPubliees = $formationRepository->count(['statut' => 'publiee']);
        $formationsBrouillon = $formationRepository->count(['statut' => 'brouillon']);
        
        $totalInscriptions = $inscriptionRepository->count([]);
        $inscriptionsEnCours = $inscriptionRepository->count(['statut' => 'en_cours']);
        $inscriptionsTerminees = $inscriptionRepository->count(['statut' => 'terminee']);

       
       

        // Statistiques boutique
        $allCommandes = $commandeRepository->findAll();
        $totalCommandes = count($allCommandes);
        $commandesEnAttente = count(array_filter($allCommandes, fn($c) => $c->getStatut() === 'en_attente'));
        $commandesLivrees = count(array_filter($allCommandes, fn($c) => $c->getStatut() === 'livree'));
        
        $chiffreAffairesBoutique = 0;
        foreach ($allCommandes as $commande) {
            if (in_array($commande->getStatut(), ['confirmee', 'en_cours', 'livree'])) {
                $chiffreAffairesBoutique += $commande->calculerTotal();
            }
        }

        // ← AJOUTEZ CES LIGNES POUR LES STATISTIQUES ÉVÉNEMENTS
        $statsEvenements = $evenementRepository->getStatistiques();

        // Dernières inscriptions
        $dernieresInscriptions = $inscriptionRepository->findBy([], ['dateInscription' => 'DESC'], 10);

        // Derniers utilisateurs
        $derniersUtilisateurs = $userRepository->findBy([], ['id' => 'DESC'], 10);

        // Formations les plus populaires
        $formationsPopulaires = $formationRepository->findBy(
            ['statut' => 'publiee'],
            ['id' => 'DESC'],
            5
        );

        // Dernières commandes
        $dernieresCommandes = $commandeRepository->findBy([], ['dateCommande' => 'DESC'], 10);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'total_users' => $totalUsers,
                'total_formateurs' => $totalFormateurs,
                'total_apprenants' => $totalApprenants,
                'total_admins' => $totalAdmins,
                'total_formations' => $totalFormations,
                'formations_publiees' => $formationsPubliees,
                'formations_brouillon' => $formationsBrouillon,
                'total_inscriptions' => $totalInscriptions,
                'inscriptions_en_cours' => $inscriptionsEnCours,
                'inscriptions_terminees' => $inscriptionsTerminees,
                'total_commandes' => $totalCommandes,
                'commandes_en_attente' => $commandesEnAttente,
                'commandes_livrees' => $commandesLivrees,
                'chiffre_affaires_boutique' => $chiffreAffairesBoutique,
                // ← AJOUTEZ CES LIGNES
                'total_evenements' => $statsEvenements['total'],
                'evenements_actifs' => $statsEvenements['actifs'],
                'evenements_a_venir' => $statsEvenements['a_venir'],
                'evenements_en_cours' => $statsEvenements['en_cours'],
            ],
            'dernieres_inscriptions' => $dernieresInscriptions,
            'derniers_utilisateurs' => $derniersUtilisateurs,
            'formations_populaires' => $formationsPopulaires,
            'dernieres_commandes' => $dernieresCommandes,
        ]);
    }
}