<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\ProgressionLecon;
use App\Repository\InscriptionRepository;
use App\Repository\FavoriRepository;
use App\Repository\LeconRepository;
use App\Repository\ProgressionLeconRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/apprenant')]
#[IsGranted('ROLE_USER')]
class ApprenantDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'apprenant_dashboard')]
    public function index(
        InscriptionRepository $inscriptionRepository,
        FavoriRepository $favoriRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est bien apprenant
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'apprenant') {
            $this->addFlash('error', 'Accès réservé aux apprenants.');
            return $this->redirectToRoute('formateur_dashboard');
        }

        $inscriptions = $inscriptionRepository->findBy(['apprenant' => $user]);
        $favoris = $favoriRepository->findBy(['apprenant' => $user]);

        // Statistiques
        $stats = [
            'total_formations' => count($inscriptions),
            'formations_en_cours' => count(array_filter($inscriptions, fn($i) => $i->getStatut() === 'en_cours')),
            'formations_terminees' => count(array_filter($inscriptions, fn($i) => $i->getStatut() === 'terminee')),
            'total_favoris' => count($favoris),
        ];

        return $this->render('apprenant/dashboard.html.twig', [
            'inscriptions' => $inscriptions,
            'favoris' => $favoris,
            'stats' => $stats,
        ]);
    }

    #[Route('/lecon/{id}/marquer-terminee', name: 'apprenant_lecon_marquer_terminee', methods: ['POST'])]
    public function marquerLeconTerminee(
        int $id,
        LeconRepository $leconRepository,
        ProgressionLeconRepository $progressionLeconRepository,
        InscriptionRepository $inscriptionRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        
        $lecon = $leconRepository->find($id);
        if (!$lecon) {
            return new JsonResponse(['success' => false, 'message' => 'Leçon non trouvée'], 404);
        }

        // Vérifier que l'apprenant est inscrit à la formation
        $inscription = $inscriptionRepository->findOneByApprenantAndFormation($user, $lecon->getFormation()->getId());
        if (!$inscription) {
            return new JsonResponse(['success' => false, 'message' => 'Vous n\'êtes pas inscrit à cette formation'], 403);
        }

        // Vérifier si la progression existe déjà
        $progression = $progressionLeconRepository->findOneByApprenantAndLecon($user, $lecon);
        
        if (!$progression) {
            $progression = new ProgressionLecon();
            $progression->setApprenant($user);
            $progression->setLecon($lecon);
        }

        $progression->setTerminee(true);
        $progression->setDateTerminee(new \DateTime());

        $em->persist($progression);

        // Mettre à jour la progression globale de l'inscription
        $formationId = $lecon->getFormation()->getId();
        $totalLecons = $leconRepository->countByFormation($formationId);
        $leconsTerminees = $progressionLeconRepository->countLeconTermineesParFormation($user, $formationId) + 1; // +1 pour celle qu'on vient de marquer

        if ($totalLecons > 0) {
            $pourcentage = min(100, round(($leconsTerminees / $totalLecons) * 100));
            $inscription->setProgression($pourcentage);

            // Si toutes les leçons sont terminées, changer le statut
            if ($leconsTerminees >= $totalLecons) {
                $inscription->setStatut('terminee');
                $inscription->setDateTerminee(new \DateTime());
            }
        }

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'progression' => $inscription->getProgression(),
            'leconsTerminees' => $leconsTerminees,
            'totalLecons' => $totalLecons,
        ]);
    }
}