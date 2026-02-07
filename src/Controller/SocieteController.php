<?php

namespace App\Controller;

use App\Entity\OffreStage;
use App\Entity\Candidature;
use App\Entity\User;
use App\Form\OffreStageType;
use App\Repository\OffreStageRepository;
use App\Repository\CandidatureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/societe/offres')]
#[IsGranted('ROLE_USER')]
class SocieteController extends AbstractController
{
    #[Route('/', name: 'societe_offres_index', methods: ['GET'])]
    public function index(OffreStageRepository $offreStageRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est une société
        if ($user->getRoleUtilisateur() !== 'societe') {
            throw $this->createAccessDeniedException('Accès réservé aux sociétés');
        }

        $offres = $offreStageRepository->findBySociete($user);

        return $this->render('societe/offres/index.html.twig', [
            'offres' => $offres,
        ]);
    }

    #[Route('/new', name: 'societe_offre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($user->getRoleUtilisateur() !== 'societe') {
            throw $this->createAccessDeniedException('Accès réservé aux sociétés');
        }

        $offre = new OffreStage();
        $offre->setSociete($user);
        
        $form = $this->createForm(OffreStageType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($offre);
            $em->flush();

            $this->addFlash('success', 'Offre de stage publiée avec succès!');
            return $this->redirectToRoute('societe_offres_index');
        }

        return $this->render('societe/offres/form.html.twig', [
            'offre' => $offre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'societe_offre_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(OffreStage $offre, CandidatureRepository $candidatureRepository): Response
    {
        if ($offre->getSociete() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $candidatures = $candidatureRepository->findByOffre($offre);

        return $this->render('societe/offres/show.html.twig', [
            'offre' => $offre,
            'candidatures' => $candidatures,
        ]);
    }

    #[Route('/{id}/edit', name: 'societe_offre_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, OffreStage $offre, EntityManagerInterface $em): Response
    {
        if ($offre->getSociete() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(OffreStageType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Offre modifiée avec succès!');
            return $this->redirectToRoute('societe_offres_index');
        }

        return $this->render('societe/offres/form.html.twig', [
            'offre' => $offre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'societe_offre_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, OffreStage $offre, EntityManagerInterface $em): Response
    {
        if ($offre->getSociete() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$offre->getId(), $request->request->get('_token'))) {
            $em->remove($offre);
            $em->flush();

            $this->addFlash('success', 'Offre supprimée avec succès');
        }

        return $this->redirectToRoute('societe_offres_index');
    }

    #[Route('/candidature/{id}/statut', name: 'societe_candidature_update_statut', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateCandidatureStatut(Candidature $candidature, Request $request, EntityManagerInterface $em): Response
    {
        if ($candidature->getOffreStage()->getSociete() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $statut = $request->request->get('statut');
        $commentaire = $request->request->get('commentaire');

        if (in_array($statut, ['en_attente', 'acceptee', 'refusee'])) {
            $candidature->setStatut($statut);
            if ($commentaire) {
                $candidature->setCommentaire($commentaire);
            }
            $em->flush();

            $this->addFlash('success', 'Statut de la candidature mis à jour');
        }

        return $this->redirectToRoute('societe_offre_show', ['id' => $candidature->getOffreStage()->getId()]);
    }
}
