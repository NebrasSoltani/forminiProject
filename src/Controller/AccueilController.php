<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccueilController extends AbstractController
{
    #[Route('/accueil', name: 'accueil')]
    public function index(UserRepository $userRepository): Response
    {
        // Récupérer les formateurs avec leurs profils
        $formateurs = $userRepository->createQueryBuilder('u')
            ->leftJoin('u.formateur', 'f')
            ->where('u.roleUtilisateur = :role')
            ->andWhere('f.id IS NOT NULL')
            ->setParameter('role', 'formateur')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();

        return $this->render('accueil.html.twig', [
            'formateurs' => $formateurs,
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