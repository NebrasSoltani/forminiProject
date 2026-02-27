<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\User;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\ParticipationEvenement;
use App\Entity\Blog; // <-- AJOUTEZ CETTE LIGNE


#[Route('/admin/evenement')]
class EvenementController extends AbstractController
{
    #[Route('/participation/{id}/ticket', name: 'admin_participation_ticket')]
    public function ticket(ParticipationEvenement $participation): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        return $this->render('admin/evenement/ticket.html.twig', [
            'participation' => $participation,
            'evenement' => $participation->getEvenement(),
            'user' => $participation->getUser(),
        ]);
    }

    #[Route('/', name: 'admin_evenement_index')]
    public function index(Request $request, EvenementRepository $evenementRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        $searchTerm = $request->query->get('search');
        $type = $request->query->get('type');
        $statut = $request->query->get('statut');
        $sortBy = $request->query->get('sort', 'dateDebut');
        $sortOrder = $request->query->get('order', 'DESC');

        $evenements = $evenementRepository->findBySearchAndFilters(
            $searchTerm,
            $type,
            $statut,
            $sortBy,
            $sortOrder
        );

        $typesDisponibles = [
            'Conférence', 'Atelier', 'Webinaire', 'Formation', 
            'Networking', 'Séminaire', 'Hackathon', 'Autre'
        ];

        return $this->render('admin/evenement/index.html.twig', [
            'evenements' => $evenements,
            'typesDisponibles' => $typesDisponibles,
            'currentSearch' => $searchTerm,
            'currentType' => $type,
            'currentStatut' => $statut,
            'currentSort' => $sortBy,
            'currentOrder' => $sortOrder,
        ]);
    }

    #[Route('/statistiques', name: 'admin_evenement_statistiques')]
    public function statistiques(EvenementRepository $evenementRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        $stats = $evenementRepository->getStatistiques();
        $topEvenements = $evenementRepository->findTopByPlaces(5);

        return $this->render('admin/evenement/statistiques.html.twig', [
            'stats' => $stats,
            'topEvenements' => $topEvenements,
        ]);
    }

    #[Route('/export-pdf', name: 'admin_evenement_export_pdf')]
    public function exportPdf(Request $request, EvenementRepository $evenementRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        $searchTerm = $request->query->get('search');
        $type = $request->query->get('type');
        $statut = $request->query->get('statut');
        $sortBy = $request->query->get('sort', 'dateDebut');
        $sortOrder = $request->query->get('order', 'DESC');

        $evenements = $evenementRepository->findBySearchAndFilters(
            $searchTerm,
            $type,
            $statut,
            $sortBy,
            $sortOrder
        );

        $stats = $evenementRepository->getStatistiques();

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($pdfOptions);
        
        $html = $this->renderView('admin/evenement/pdf.html.twig', [
            'evenements' => $evenements,
            'stats' => $stats,
            'dateGeneration' => new \DateTime(),
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="evenements_' . date('Y-m-d_His') . '.pdf"',
            ]
        );
    }

    #[Route('/{id}/pdf', name: 'admin_evenement_single_pdf')]
    public function singlePdf(Evenement $evenement): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($pdfOptions);
        
        $html = $this->renderView('admin/evenement/single_pdf.html.twig', [
            'evenement' => $evenement,
            'dateGeneration' => new \DateTime(),
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="evenement_' . $evenement->getId() . '_' . date('Y-m-d') . '.pdf"',
            ]
        );
    }

    
    #[Route('/new', name: 'admin_evenement_new')]
public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
{
    $user = $this->getUser();
    if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
        $this->addFlash('error', 'Accès réservé aux administrateurs.');
        return $this->redirectToRoute('accueil');
    }

    $evenement = new Evenement();
    // ✅ DÉFINIR L'ORGANISATEUR AVANT LA CRÉATION DU FORMULAIRE
    $evenement->setOrganisateur($user);
    
    $form = $this->createForm(EvenementType::class, $evenement);
    
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        // Afficher les erreurs de validation
        if (!$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }
        
        if ($form->isValid()) {
            // L'organisateur est déjà défini plus haut
            
            // Gestion de l'image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    // Créer le dossier s'il n'existe pas
                    $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/evenements';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $imageFile->move($uploadDir, $newFilename);
                    $evenement->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                    return $this->render('admin/evenement/new.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
            }
            
            try {
                $em->persist($evenement);
                $em->flush();
                
                $this->addFlash('success', 'Événement créé avec succès !');
                return $this->redirectToRoute('admin_evenement_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création de l\'événement : ' . $e->getMessage());
            }
        }
    }

    return $this->render('admin/evenement/new.html.twig', [
        'form' => $form->createView(),
    ]);
}
    #[Route('/{id}/edit', name: 'admin_evenement_edit')]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }
    
        $form = $this->createForm(EvenementType::class, $evenement);
        
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/evenements',
                        $newFilename
                    );
                    
                    if ($evenement->getImage()) {
                        $oldImagePath = $this->getParameter('kernel.project_dir').'/public/uploads/evenements/'.$evenement->getImage();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $evenement->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $em->flush();
            $this->addFlash('success', 'Événement modifié avec succès !');
            return $this->redirectToRoute('admin_evenement_index');
        }
    
        return $this->render('admin/evenement/edit.html.twig', [
            'form' => $form->createView(),
            'evenement' => $evenement,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_evenement_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Evenement $evenement, 
        EntityManagerInterface $entityManager
    ): Response
    {
        // Vérification des droits d'accès
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }
        
        // Vérification du token CSRF
        if ($this->isCsrfTokenValid('delete'.$evenement->getId(), $request->request->get('_token'))) {
            // IMPORTANT : Ajoutez cet import en haut de votre fichier
            // use App\Entity\Blog;
            
            // Supprimer d'abord les articles de blog liés
            $blogPosts = $entityManager->getRepository(Blog::class)
                ->findBy(['evenement' => $evenement]);
                
            foreach ($blogPosts as $blogPost) {
                $entityManager->remove($blogPost);
            }
            
            // Supprimer l'événement
            $entityManager->remove($evenement);
            $entityManager->flush();
            
            $this->addFlash('success', 'Événement supprimé avec succès');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
        return $this->redirectToRoute('admin_evenement_index');
    }

    #[Route('/{id}/participants', name: 'admin_evenement_participants')]
    public function participants(Evenement $evenement): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        return $this->render('admin/evenement/participants.html.twig', [
            'evenement' => $evenement,
            'participations' => $evenement->getParticipations(),
        ]);
    }
}