<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\FormationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/formateur')]
#[IsGranted('ROLE_USER')]
class FormateurDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'formateur_dashboard')]
    public function index(FormationRepository $formationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est bien formateur
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'formateur') {
            $this->addFlash('error', 'Accès réservé aux formateurs.');
            return $this->redirectToRoute('apprenant_dashboard');
        }

        $formations = $formationRepository->findBy(['formateur' => $user]);
        
        // Statistiques
        $stats = [
            'total_formations' => count($formations),
            'formations_publiees' => count(array_filter($formations, fn($f) => $f->getStatut() === 'publiee')),
            'formations_brouillon' => count(array_filter($formations, fn($f) => $f->getStatut() === 'brouillon')),
            'total_inscrits' => array_sum(array_map(fn($f) => $f->getInscriptions()->count(), $formations)),
        ];

        return $this->render('formateur/dashboard.html.twig', [
            'formations' => $formations,
            'stats' => $stats,
        ]);
    }
}