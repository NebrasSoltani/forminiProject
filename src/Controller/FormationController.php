<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Form\FormationType;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/formateur/formation')]
#[IsGranted('ROLE_USER')]
class FormationController extends AbstractController
{
    #[Route('/', name: 'formation_index', methods: ['GET'])]
    public function index(FormationRepository $formationRepository): Response
    {
        $formations = $formationRepository->findBy(['formateur' => $this->getUser()]);

        return $this->render('formation/index.html.twig', [
            'formations' => $formations,
        ]);
    }

    #[Route('/new', name: 'formation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $formation = new Formation();
        $formation->setFormateur($this->getUser());

        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload d'image
            $imageFile = $form->get('imageCouverture')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    $formation->setImageCouverture($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                }
            }

            // Déterminer le statut selon l'action
            $action = $request->request->get('action');
            if ($action === 'brouillon') {
                $formation->setStatut('brouillon');
                $this->addFlash('success', 'Formation sauvegardée en brouillon !');
            } elseif ($action === 'soumettre') {
                $formation->setStatut('en_attente');
                $this->addFlash('success', 'Formation soumise pour validation !');
            } else {
                $formation->setStatut('publiee');
                $formation->setDatePublication(new \DateTime());
                $this->addFlash('success', 'Formation publiée avec succès !');
            }

            $em->persist($formation);
            $em->flush();

            return $this->redirectToRoute('formation_index');
        }

        return $this->render('formation/new.html.twig', [
            'formation' => $formation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'formation_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Formation $formation): Response
    {
        if ($formation->getFormateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('formation/show.html.twig', [
            'formation' => $formation,
        ]);
    }

    #[Route('/{id}/edit', name: 'formation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Formation $formation, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        if ($formation->getFormateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload d'image
            $imageFile = $form->get('imageCouverture')->getData();
            if ($imageFile) {
                // Supprimer l'ancienne image si elle existe
                if ($formation->getImageCouverture()) {
                    $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/images/' . $formation->getImageCouverture();
                    if (file_exists($oldImagePath)) {
                        @unlink($oldImagePath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    $formation->setImageCouverture($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                }
            }

            $em->flush();

            $this->addFlash('success', 'Formation modifiée avec succès !');
            return $this->redirectToRoute('formation_index');
        }

        return $this->render('formation/edit.html.twig', [
            'formation' => $formation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'formation_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Formation $formation, 
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$formation->getId(), $request->request->get('_token'))) {
            
            // Vérifier que c'est bien le formateur
            if ($formation->getFormateur() !== $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer cette formation.');
                return $this->redirectToRoute('formation_index');
            }

            // Vérifier les inscriptions
            if ($formation->getInscriptions()->count() > 0) {
                $this->addFlash('error', '⛔ Impossible de supprimer cette formation car ' . $formation->getInscriptions()->count() . ' apprenant(s) y sont inscrits.');
                return $this->redirectToRoute('formation_index');
            }

            try {
                // Supprimer l'image si elle existe
                if ($formation->getImageCouverture()) {
                    $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/images/' . $formation->getImageCouverture();
                    if (file_exists($imagePath)) {
                        @unlink($imagePath);
                    }
                }

                $titre = $formation->getTitre();
                $entityManager->remove($formation);
                $entityManager->flush();

                $this->addFlash('success', '✅ Formation "' . $titre . '" supprimée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', '❌ Erreur lors de la suppression : ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', '❌ Token CSRF invalide.');
        }

        return $this->redirectToRoute('formation_index');
    }
}