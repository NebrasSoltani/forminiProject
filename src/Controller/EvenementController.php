<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\User;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options; // Cette ligne était manquante
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/evenement')]
class EvenementController extends AbstractController
{
    #[Route('/', name: 'admin_evenement_index')]
    public function index(Request $request, EvenementRepository $evenementRepository): Response
    {
        // Vérifier que l'utilisateur est admin
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        // Récupération des paramètres de recherche et tri
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

        // Types disponibles pour le filtre
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

        // Récupérer les mêmes filtres que l'index
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

        // Configuration de Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($pdfOptions);
        
        // Générer le HTML
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
                    $evenement->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $evenement->setOrganisateur($user);
            $em->persist($evenement);
            $em->flush();

            $this->addFlash('success', 'Événement créé avec succès !');
            return $this->redirectToRoute('admin_evenement_index');
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
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        if ($this->isCsrfTokenValid('delete'.$evenement->getId(), $request->request->get('_token'))) {
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