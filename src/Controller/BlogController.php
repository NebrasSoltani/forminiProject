<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Entity\User;
use App\Entity\Evenement;
use App\Form\BlogType;
use App\Form\BlogFilterType;
use App\Repository\BlogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/admin/blog')]
class BlogController extends AbstractController
{
    #[Route('/', name: 'admin_blog_index')]
    public function index(Request $request, BlogRepository $blogRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        $filterForm = $this->createForm(BlogFilterType::class);
        $filterForm->handleRequest($request);

        $filters = [];
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $filters = $filterForm->getData();
        }

        // Récupérer aussi les paramètres de l'URL pour le tri et les filtres actifs
        $currentFilters = array_filter($request->query->all(), function($value, $key) {
            return !in_array($key, ['sort', 'direction']) && !empty($value);
        }, ARRAY_FILTER_USE_BOTH);

        $blogs = $blogRepository->findByFilters($filters);

        // Tri manuel si nécessaire
        $sortField = $request->query->get('sort', 'datePublication');
        $sortDirection = $request->query->get('direction', 'DESC');

        return $this->render('admin/blog/index.html.twig', [
            'blogs' => $blogs,
            'filterForm' => $filterForm->createView(),
            'currentFilters' => $currentFilters,
            'currentSort' => $sortField,
            'currentDirection' => $sortDirection,
        ]);
    }

    #[Route('/statistiques', name: 'admin_blog_statistiques')]
public function statistiques(BlogRepository $blogRepository, EntityManagerInterface $em): Response
{
    $user = $this->getUser();
    if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
        $this->addFlash('error', 'Accès réservé aux administrateurs.');
        return $this->redirectToRoute('accueil');
    }

    // Statistiques de base
    $totalBlogs = $blogRepository->count([]);
    $blogsPublies = $blogRepository->count(['isPublie' => true]);
    $blogsNonPublies = $blogRepository->count(['isPublie' => false]);
    
    // Blogs avec événements
    $qb = $blogRepository->createQueryBuilder('b')
        ->select('COUNT(b.id)')
        ->where('b.evenement IS NOT NULL');
    $blogsAvecEvenements = $qb->getQuery()->getSingleScalarResult();

    // Moyenne par mois - Utilisation de SQL natif
    $conn = $em->getConnection();
    $sql = "SELECT COUNT(DISTINCT DATE_FORMAT(date_publication, '%Y-%m')) as months FROM blog";
    $stmt = $conn->prepare($sql);
    $result = $stmt->executeQuery();
    $nombreMois = $result->fetchOne();
    $moyenneParMois = $nombreMois > 0 ? round($totalBlogs / $nombreMois, 1) : 0;

    // Blogs par catégorie
    $qb = $blogRepository->createQueryBuilder('b')
        ->select('b.categorie, COUNT(b.id) as total')
        ->groupBy('b.categorie')
        ->orderBy('total', 'DESC');
    $blogsByCategory = $qb->getQuery()->getResult();

    // Top 10 auteurs
    $qb = $blogRepository->createQueryBuilder('b')
        ->select('CONCAT(u.prenom, \' \', u.nom) as auteur, COUNT(b.id) as total')
        ->join('b.auteur', 'u')
        ->groupBy('u.id')
        ->orderBy('total', 'DESC')
        ->setMaxResults(10);
    $blogsByAuthor = $qb->getQuery()->getResult();

    // Évolution par mois (6 derniers mois) - Utilisation de SQL natif
    $sql = "SELECT DATE_FORMAT(date_publication, '%Y-%m') as mois, COUNT(id) as total 
            FROM blog 
            WHERE date_publication >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY mois 
            ORDER BY mois ASC";
    $stmt = $conn->prepare($sql);
    $result = $stmt->executeQuery();
    $blogsByMonth = $result->fetchAllAssociative();

    // Formater les noms de mois en français
    $moisFr = ['01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril', 
               '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
               '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'];
    
    foreach ($blogsByMonth as &$item) {
        $parts = explode('-', $item['mois']);
        if (isset($parts[1]) && isset($moisFr[$parts[1]])) {
            $item['mois'] = $moisFr[$parts[1]] . ' ' . $parts[0];
        }
    }

    // Blogs récents (10 derniers)
    $blogsRecents = $blogRepository->findBy([], ['datePublication' => 'DESC'], 10);

    return $this->render('admin/blog/statistiques.html.twig', [
        'totalBlogs' => $totalBlogs,
        'blogsPublies' => $blogsPublies,
        'blogsNonPublies' => $blogsNonPublies,
        'blogsBrouillons' => $blogsNonPublies,
        'blogsAvecEvenements' => $blogsAvecEvenements,
        'moyenneParMois' => $moyenneParMois,
        'blogsByCategory' => $blogsByCategory,
        'blogsByAuthor' => $blogsByAuthor,
        'blogsByMonth' => $blogsByMonth,
        'blogsRecents' => $blogsRecents,
    ]);
}
    #[Route('/export-pdf', name: 'admin_blog_export_pdf')]
    public function exportPdf(Request $request, BlogRepository $blogRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        // Récupérer les filtres depuis la session ou la requête
        $filters = $request->query->all();
        $blogs = $blogRepository->findByFilters($filters);

        // Configuration de Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);

        // Générer le contenu HTML
        $html = $this->renderView('admin/blog/pdf_export.html.twig', [
            'blogs' => $blogs,
            'date' => new \DateTime(),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Générer le nom du fichier
        $filename = 'blogs_export_' . date('Y-m-d_H-i-s') . '.pdf';

        // Retourner le PDF en téléchargement
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    #[Route('/new', name: 'admin_blog_new')]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {   
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        $blog = new Blog();
        
        // Initialiser les valeurs par défaut AVANT la création du formulaire
        $blog->setDatePublication(new \DateTime());
        $blog->setAuteur($user);
        $blog->setIsPublie(false);
        
        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de l'image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    // Créer le dossier s'il n'existe pas
                    $uploadsDir = $this->getParameter('kernel.project_dir').'/public/uploads/blogs';
                    if (!is_dir($uploadsDir)) {
                        mkdir($uploadsDir, 0777, true);
                    }

                    $imageFile->move($uploadsDir, $newFilename);
                    $blog->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                    return $this->render('admin/blog/new.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
            }

            try {
                $em->persist($blog);
                $em->flush();

                $this->addFlash('success', 'Blog créé avec succès !');
                return $this->redirectToRoute('admin_blog_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du blog : ' . $e->getMessage());
            }
        }

        return $this->render('admin/blog/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_blog_edit')]
    public function edit(Request $request, Blog $blog, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de la nouvelle image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $uploadsDir = $this->getParameter('kernel.project_dir').'/public/uploads/blogs';
                    if (!is_dir($uploadsDir)) {
                        mkdir($uploadsDir, 0777, true);
                    }

                    $imageFile->move($uploadsDir, $newFilename);
                    
                    // Supprimer l'ancienne image
                    if ($blog->getImage()) {
                        $oldImagePath = $uploadsDir . '/' . $blog->getImage();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $blog->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                }
            }

            try {
                $em->flush();
                $this->addFlash('success', 'Blog modifié avec succès !');
                return $this->redirectToRoute('admin_blog_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
            }
        }

        return $this->render('admin/blog/edit.html.twig', [
            'form' => $form->createView(),
            'blog' => $blog,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_blog_delete', methods: ['POST'])]
    public function delete(Request $request, Blog $blog, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        if ($this->isCsrfTokenValid('delete'.$blog->getId(), $request->request->get('_token'))) {
            // Supprimer l'image associée
            if ($blog->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir').'/public/uploads/blogs/'.$blog->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $em->remove($blog);
            $em->flush();

            $this->addFlash('success', 'Blog supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_blog_index');
    }

    #[Route('/{id}', name: 'admin_blog_show')]
    public function show(Blog $blog): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        return $this->render('admin/blog/show.html.twig', [
            'blog' => $blog,
        ]);
    }
}