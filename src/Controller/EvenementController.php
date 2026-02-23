<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\User;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
<<<<<<< Updated upstream
=======
use App\Entity\ParticipationEvenement;
use App\Entity\Blog;
use App\Repository\LiveReactionRepository;
use App\Repository\LiveCommentRepository;

>>>>>>> Stashed changes

#[Route('/admin/evenement')]
class EvenementController extends AbstractController
{
    #[Route('/', name: 'admin_evenement_index')]
    public function index(EvenementRepository $evenementRepository): Response
    {
        // Vérifier que l'utilisateur est admin
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        $evenements = $evenementRepository->findBy([], ['dateDebut' => 'DESC']);

        return $this->render('admin/evenement/index.html.twig', [
            'evenements' => $evenements,
        ]);
    }

<<<<<<< Updated upstream
=======
    #[Route('/statistiques', name: 'admin_evenement_statistiques')]
    public function statistiques(
        EvenementRepository $evenementRepository,
        LiveReactionRepository $reactionRepo,
        LiveCommentRepository $commentRepo
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        $stats = $evenementRepository->getStatistiques();
        $topEvenements = $evenementRepository->findTopByPlaces(5);

        // Live stats
        $liveStats = [
            'total_reactions' => $reactionRepo->getTotalReactions(),
            'total_comments' => $commentRepo->getTotalComments(),
            'reaction_distribution' => $reactionRepo->getReactionDistribution(),
            'reactions_by_event' => $reactionRepo->getStatsByEvent(),
            'comments_by_event' => $commentRepo->getStatsByEvent(),
        ];

        return $this->render('admin/evenement/statistiques.html.twig', [
            'stats' => $stats,
            'topEvenements' => $topEvenements,
            'liveStats' => $liveStats,
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

    
>>>>>>> Stashed changes
    #[Route('/new', name: 'admin_evenement_new')]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        // Vérifier que l'utilisateur est admin
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de l'image
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
                    $evenement->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

<<<<<<< Updated upstream
            $evenement->setOrganisateur($user);

            $em->persist($evenement);
            $em->flush();

            $this->addFlash('success', 'Événement créé avec succès !');
            return $this->redirectToRoute('admin_evenement_index');
=======
            // Gestion de l'image 360
            $image360File = $form->get('image360')->getData();
            if ($image360File) {
                $originalFilename = pathinfo($image360File->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename360 = '360-'.$safeFilename.'-'.uniqid().'.'.$image360File->guessExtension();

                try {
                    $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/evenements';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $image360File->move($uploadDir, $newFilename360);
                    $evenement->setImage360($newFilename360);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image 360 : ' . $e->getMessage());
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
>>>>>>> Stashed changes
        }

        return $this->render('admin/evenement/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_evenement_edit')]
<<<<<<< Updated upstream
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        // Vérifier que l'utilisateur est admin
=======
    public function edit(
        Request $request, 
        Evenement $evenement, 
        EntityManagerInterface $em, 
        SluggerInterface $slugger,
        \App\Service\LiveResumeService $resumeService
    ): Response {
>>>>>>> Stashed changes
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }
<<<<<<< Updated upstream
=======
    
        // Store original live status to detect end of live
        $wasLive = $evenement->isLive();
>>>>>>> Stashed changes

        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
<<<<<<< Updated upstream
            // Gérer l'upload de la nouvelle image
=======
            // ... image upload logic remains same ...
>>>>>>> Stashed changes
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                // (Existing image code)
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/evenements',
                        $newFilename
                    );
                    
                    // Supprimer l'ancienne image
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
            // Gestion de l'image 360
            $image360File = $form->get('image360')->getData();
            if ($image360File) {
                $originalFilename = pathinfo($image360File->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename360 = '360-'.$safeFilename.'-'.uniqid().'.'.$image360File->guessExtension();

                try {
                    $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/evenements';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $image360File->move($uploadDir, $newFilename360);
                    
                    if ($evenement->getImage360()) {
                        $oldPath = $uploadDir . '/' . $evenement->getImage360();
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    $evenement->setImage360($newFilename360);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image 360.');
                }
            }

            $em->flush();

<<<<<<< Updated upstream
=======
            // Trigger AI Resume if live was just stopped
            if ($wasLive && !$evenement->isLive()) {
                $resumeService->generateAndSaveResume($evenement);
                $this->addFlash('info', 'Le live est terminé. Un résumé intelligent a été généré via Ollama.');
            }

>>>>>>> Stashed changes
            $this->addFlash('success', 'Événement modifié avec succès !');
            return $this->redirectToRoute('admin_evenement_index');
        }

        return $this->render('admin/evenement/edit.html.twig', [
            'form' => $form->createView(),
            'evenement' => $evenement,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $em): Response
    {
        // Vérifier que l'utilisateur est admin
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        if ($this->isCsrfTokenValid('delete'.$evenement->getId(), $request->request->get('_token'))) {
            // Supprimer l'image
            if ($evenement->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir').'/public/uploads/evenements/'.$evenement->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $em->remove($evenement);
            $em->flush();

            $this->addFlash('success', 'Événement supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_evenement_index');
    }
}
