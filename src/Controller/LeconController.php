<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\Lecon;
use App\Form\LeconType;
use App\Repository\FormationRepository;
use App\Repository\LeconRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/formateur/formation/{formationId}/lecon')]
#[IsGranted('ROLE_USER')]
class LeconController extends AbstractController
{
    #[Route('/', name: 'lecon_index', methods: ['GET'])]
    public function index(int $formationId, FormationRepository $formationRepository, LeconRepository $leconRepository): Response
    {
        $formation = $formationRepository->find($formationId);
        
        if (!$formation) {
            throw $this->createNotFoundException('Formation non trouvée');
        }

        // Vérifier que l'utilisateur est le formateur
        if ($formation->getFormateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $lecons = $leconRepository->findByFormationOrdered($formationId);

        return $this->render('lecon/index.html.twig', [
            'formation' => $formation,
            'lecons' => $lecons,
        ]);
    }

    #[Route('/new', name: 'lecon_new', methods: ['GET', 'POST'])]
    public function new(Request $request, int $formationId, FormationRepository $formationRepository, EntityManagerInterface $em): Response
    {
        $formation = $formationRepository->find($formationId);
        
        if (!$formation) {
            throw $this->createNotFoundException('Formation non trouvée');
        }

        if ($formation->getFormateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $lecon = new Lecon();
        $lecon->setFormation($formation);
        
        // Définir l'ordre automatiquement
        $dernierOrdre = $em->getRepository(Lecon::class)
            ->createQueryBuilder('l')
            ->select('MAX(l.ordre)')
            ->where('l.formation = :formation')
            ->setParameter('formation', $formation)
            ->getQuery()
            ->getSingleScalarResult();
        
        $lecon->setOrdre(($dernierOrdre ?? 0) + 1);

        $form = $this->createForm(LeconType::class, $lecon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($lecon);
            $em->flush();

            $this->addFlash('success', 'Leçon créée avec succès !');
            return $this->redirectToRoute('lecon_index', ['formationId' => $formationId]);
        }

        return $this->render('lecon/new.html.twig', [
            'formation' => $formation,
            'lecon' => $lecon,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'lecon_show', methods: ['GET'])]
    public function show(int $formationId, int $id, FormationRepository $formationRepository, LeconRepository $leconRepository): Response
    {
        $formation = $formationRepository->find($formationId);
        $lecon = $leconRepository->find($id);
        
        if (!$formation || !$lecon) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() || $lecon->getFormation() !== $formation) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('lecon/show.html.twig', [
            'formation' => $formation,
            'lecon' => $lecon,
        ]);
    }

    #[Route('/{id}/edit', name: 'lecon_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $formationId, int $id, FormationRepository $formationRepository, LeconRepository $leconRepository, EntityManagerInterface $em): Response
    {
        $formation = $formationRepository->find($formationId);
        $lecon = $leconRepository->find($id);
        
        if (!$formation || !$lecon) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() || $lecon->getFormation() !== $formation) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(LeconType::class, $lecon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Leçon modifiée avec succès !');
            return $this->redirectToRoute('lecon_index', ['formationId' => $formationId]);
        }

        return $this->render('lecon/edit.html.twig', [
            'formation' => $formation,
            'lecon' => $lecon,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'lecon_delete', methods: ['POST'])]
    public function delete(Request $request, int $formationId, int $id, FormationRepository $formationRepository, LeconRepository $leconRepository, EntityManagerInterface $em): Response
    {
        $formation = $formationRepository->find($formationId);
        $lecon = $leconRepository->find($id);
        
        if (!$formation || !$lecon) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() || $lecon->getFormation() !== $formation) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$lecon->getId(), $request->request->get('_token'))) {
            $em->remove($lecon);
            $em->flush();

            $this->addFlash('success', 'Leçon supprimée avec succès !');
        }

        return $this->redirectToRoute('lecon_index', ['formationId' => $formationId]);
    }

    #[Route('/{id}/up', name: 'lecon_move_up', methods: ['POST'])]
    public function moveUp(Request $request, int $formationId, int $id, FormationRepository $formationRepository, LeconRepository $leconRepository, EntityManagerInterface $em): Response
    {
        $formation = $formationRepository->find($formationId);
        $lecon = $leconRepository->find($id);
        
        if (!$formation || !$lecon) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() || $lecon->getFormation() !== $formation) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('move'.$lecon->getId(), $request->request->get('_token'))) {
            // Trouver la leçon précédente
            $previousLecon = $leconRepository->createQueryBuilder('l')
                ->where('l.formation = :formation')
                ->andWhere('l.ordre < :ordre')
                ->setParameter('formation', $formation)
                ->setParameter('ordre', $lecon->getOrdre())
                ->orderBy('l.ordre', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($previousLecon) {
                $tempOrdre = $lecon->getOrdre();
                $lecon->setOrdre($previousLecon->getOrdre());
                $previousLecon->setOrdre($tempOrdre);
                $em->flush();
            }
        }

        return $this->redirectToRoute('lecon_index', ['formationId' => $formationId]);
    }

    #[Route('/{id}/down', name: 'lecon_move_down', methods: ['POST'])]
    public function moveDown(Request $request, int $formationId, int $id, FormationRepository $formationRepository, LeconRepository $leconRepository, EntityManagerInterface $em): Response
    {
        $formation = $formationRepository->find($formationId);
        $lecon = $leconRepository->find($id);
        
        if (!$formation || !$lecon) {
            throw $this->createNotFoundException();
        }

        if ($formation->getFormateur() !== $this->getUser() || $lecon->getFormation() !== $formation) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('move'.$lecon->getId(), $request->request->get('_token'))) {
            // Trouver la leçon suivante
            $nextLecon = $leconRepository->createQueryBuilder('l')
                ->where('l.formation = :formation')
                ->andWhere('l.ordre > :ordre')
                ->setParameter('formation', $formation)
                ->setParameter('ordre', $lecon->getOrdre())
                ->orderBy('l.ordre', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($nextLecon) {
                $tempOrdre = $lecon->getOrdre();
                $lecon->setOrdre($nextLecon->getOrdre());
                $nextLecon->setOrdre($tempOrdre);
                $em->flush();
            }
        }

        return $this->redirectToRoute('lecon_index', ['formationId' => $formationId]);
    }
}