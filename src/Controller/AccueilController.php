<?php

namespace App\Controller;

use App\Repository\EvenementRepository;
use App\Repository\ParticipationEvenementRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccueilController extends AbstractController
{
    #[Route('/', name: 'accueil')]
    public function index(
        UserRepository $userRepository,
        EvenementRepository $evenementRepository,
        ParticipationEvenementRepository $participationRepo
    ): Response {
        // Formateurs avec leurs profils
        $formateurs = $userRepository->createQueryBuilder('u')
            ->leftJoin('u.formateur', 'f')
            ->where('u.roleUtilisateur = :role')
            ->andWhere('f.id IS NOT NULL')
            ->setParameter('role', 'formateur')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();

        // Événements actifs pour la section accueil
        $evenements = $evenementRepository->findActiveEvents();
        $user = $this->getUser();
        $idsParticipation = [];
        if ($user) {
            foreach ($evenements as $e) {
                if ($participationRepo->isParticipant($user, $e)) {
                    $idsParticipation[$e->getId()] = true;
                }
            }
        }

        return $this->render('accueil.html.twig', [
            'formateurs' => $formateurs,
            'evenements' => $evenements,
            'ids_participation' => $idsParticipation,
        ]);
    }

    #[Route('/formateurs', name: 'formateurs_list')]
    public function listFormateurs(UserRepository $userRepository): Response
    {
        // Récupérer tous les formateurs avec leurs profils
        $formateurs = $userRepository->createQueryBuilder('u')
            ->leftJoin('u.formateur', 'f')
            ->where('u.roleUtilisateur = :role')
            ->andWhere('f.id IS NOT NULL')
            ->setParameter('role', 'formateur')
            ->orderBy('f.experienceAnnees', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('formateurs/list.html.twig', [
            'formateurs' => $formateurs,
        ]);
    }
}