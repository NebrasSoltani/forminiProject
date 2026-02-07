<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\OffreStageRepository;
use App\Repository\CandidatureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class SocieteDashboardController extends AbstractController
{
    #[Route('/societe/dashboard', name: 'societe_dashboard')]
    public function index(
        OffreStageRepository $offreStageRepository,
        CandidatureRepository $candidatureRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($user->getRoleUtilisateur() !== 'societe') {
            throw $this->createAccessDeniedException('Accès réservé aux sociétés');
        }

        // Récupérer toutes les offres de la société
        $offres = $offreStageRepository->findBySociete($user);
        
        // Statistiques générales
        $totalOffres = count($offres);
        $offresPubliees = 0;
        $offresFermees = 0;
        $offresExpirees = 0;
        $totalCandidatures = 0;
        $candidaturesEnAttente = 0;
        $candidaturesAcceptees = 0;
        $candidaturesRefusees = 0;
        
        foreach ($offres as $offre) {
            switch ($offre->getStatut()) {
                case 'publiee':
                    $offresPubliees++;
                    break;
                case 'fermee':
                    $offresFermees++;
                    break;
                case 'expiree':
                    $offresExpirees++;
                    break;
            }
            
            $candidatures = $offre->getCandidatures();
            $totalCandidatures += count($candidatures);
            
            foreach ($candidatures as $candidature) {
                switch ($candidature->getStatut()) {
                    case 'en_attente':
                        $candidaturesEnAttente++;
                        break;
                    case 'acceptee':
                        $candidaturesAcceptees++;
                        break;
                    case 'refusee':
                        $candidaturesRefusees++;
                        break;
                }
            }
        }
        
        // Récupérer les dernières candidatures reçues
        $dernieresCandidatures = [];
        foreach ($offres as $offre) {
            foreach ($offre->getCandidatures() as $candidature) {
                $dernieresCandidatures[] = $candidature;
            }
        }
        
        // Trier par date décroissante
        usort($dernieresCandidatures, function($a, $b) {
            return $b->getDateCandidature() <=> $a->getDateCandidature();
        });
        
        // Limiter aux 10 dernières
        $dernieresCandidatures = array_slice($dernieresCandidatures, 0, 10);

        return $this->render('societe/dashboard.html.twig', [
            'totalOffres' => $totalOffres,
            'offresPubliees' => $offresPubliees,
            'offresFermees' => $offresFermees,
            'offresExpirees' => $offresExpirees,
            'totalCandidatures' => $totalCandidatures,
            'candidaturesEnAttente' => $candidaturesEnAttente,
            'candidaturesAcceptees' => $candidaturesAcceptees,
            'candidaturesRefusees' => $candidaturesRefusees,
            'dernieresCandidatures' => $dernieresCandidatures,
            'offres' => $offres,
        ]);
    }
}
