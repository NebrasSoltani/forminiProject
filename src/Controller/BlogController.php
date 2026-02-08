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

#[Route('/admin/blog')]
class BlogController extends AbstractController
{
    #[Route('/', name: 'admin_blog_index')]
    public function index(Request $request, BlogRepository $blogRepository, EntityManagerInterface $em): Response
    {
        // Vérifier que l'utilisateur est admin
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        // Récupérer les paramètres GET
        $search = $request->query->get('search', '');
        $categorie = $request->query->get('categorie', '');
        $isPublie = $request->query->get('isPublie', '');
        $auteur = $request->query->get('auteur', '');
        $evenement = $request->query->get('evenement', '');
        $dateFrom = $request->query->get('dateFrom', '');
        $dateTo = $request->query->get('dateTo', '');
        
        // Préparer les filtres pour la recherche
        $filters = [];
        
        if ($search !== '') {
            $filters['search'] = $search;
        }
        
        if ($categorie !== '') {
            $filters['categorie'] = $categorie;
        }
        
        if ($isPublie !== '') {
            $filters['isPublie'] = $isPublie;
        }
        
        if ($auteur !== '') {
            $filters['auteur'] = $auteur;
        }
        
        if ($evenement !== '') {
            $filters['evenement'] = $evenement;
        }
        
        if ($dateFrom !== '') {
            $filters['dateFrom'] = $dateFrom;
        }
        
        if ($dateTo !== '') {
            $filters['dateTo'] = $dateTo;
        }

        // Préparer les données pour le formulaire
        $formData = [
            'search' => $search,
            'categorie' => $categorie,
            'isPublie' => $isPublie,
        ];
        
        // Convertir les IDs en objets pour le formulaire
        if ($auteur !== '') {
            $auteurObj = $em->getRepository(User::class)->find($auteur);
            if ($auteurObj) {
                $formData['auteur'] = $auteurObj;
            }
        }
        
        if ($evenement !== '') {
            $evenementObj = $em->getRepository(Evenement::class)->find($evenement);
            if ($evenementObj) {
                $formData['evenement'] = $evenementObj;
            }
        }
        
        if ($dateFrom !== '') {
            try {
                $formData['dateFrom'] = new \DateTime($dateFrom);
            } catch (\Exception $e) {
                // Date invalide
            }
        }
        
        if ($dateTo !== '') {
            try {
                $formData['dateTo'] = new \DateTime($dateTo);
            } catch (\Exception $e) {
                // Date invalide
            }
        }

        // Créer le formulaire de filtres
        $categories = $blogRepository->getAllCategories();
        $filterForm = $this->createForm(BlogFilterType::class, $formData, [
            'categories' => $categories
        ]);

        // Gestion du tri
        $sortField = $request->query->get('sort', 'datePublication');
        $sortDirection = $request->query->get('direction', 'DESC');
        
        // Mapping des champs de tri
        $fieldMapping = [
            'titre' => 'b.titre',
            'datePublication' => 'b.datePublication',
            'categorie' => 'b.categorie',
            'auteur' => 'a.nom',
            'statut' => 'b.isPublie'
        ];

        $sorting = [
            'field' => $fieldMapping[$sortField] ?? 'b.datePublication',
            'direction' => in_array(strtoupper($sortDirection), ['ASC', 'DESC']) ? strtoupper($sortDirection) : 'DESC'
        ];

        // Récupérer les blogs filtrés et triés
        $blogs = $blogRepository->searchAndSort($filters, $sorting);

        return $this->render('admin/blog/index.html.twig', [
            'blogs' => $blogs,
            'filterForm' => $filterForm->createView(),
            'categories' => $categories,
            'currentSort' => $sortField,
            'currentDirection' => $sortDirection,
            'currentFilters' => $filters,
        ]);
    }

    #[Route('/export/pdf', name: 'admin_blog_export_pdf')]
    public function exportPdf(Request $request, BlogRepository $blogRepository): Response
    {
        // Vérifier que l'utilisateur est admin
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        // Récupérer les filtres depuis les paramètres GET
        $filters = [];
        
        $search = $request->query->get('search', '');
        if ($search !== '') $filters['search'] = $search;
        
        $categorie = $request->query->get('categorie', '');
        if ($categorie !== '') $filters['categorie'] = $categorie;
        
        $isPublie = $request->query->get('isPublie', '');
        if ($isPublie !== '') $filters['isPublie'] = $isPublie;
        
        $auteur = $request->query->get('auteur', '');
        if ($auteur !== '') $filters['auteur'] = $auteur;
        
        $evenement = $request->query->get('evenement', '');
        if ($evenement !== '') $filters['evenement'] = $evenement;
        
        $dateFrom = $request->query->get('dateFrom', '');
        if ($dateFrom !== '') $filters['dateFrom'] = $dateFrom;
        
        $dateTo = $request->query->get('dateTo', '');
        if ($dateTo !== '') $filters['dateTo'] = $dateTo;

        // Récupérer les blogs filtrés
        $sorting = [
            'field' => 'b.datePublication',
            'direction' => 'DESC'
        ];
        $blogs = $blogRepository->searchAndSort($filters, $sorting);

        // Créer le PDF avec TCPDF
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        
        // Informations du document
        $pdf->SetCreator('EventBlogs');
        $pdf->SetAuthor($user->getPrenom() . ' ' . $user->getNom());
        $pdf->SetTitle('Export des Blogs');
        $pdf->SetSubject('Liste des blogs');
        
        // Supprimer l'en-tête et le pied de page par défaut
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Définir les marges
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        
        // Ajouter une page
        $pdf->AddPage();
        
        // Titre
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetTextColor(0, 123, 255);
        $pdf->Cell(0, 10, 'Export des Blogs', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(102, 102, 102);
        $pdf->Cell(0, 5, 'Généré le ' . (new \DateTime())->format('d/m/Y à H:i'), 0, 1, 'C');
        $pdf->Cell(0, 5, 'Total: ' . count($blogs) . ' blog' . (count($blogs) > 1 ? 's' : ''), 0, 1, 'C');
        $pdf->Ln(5);
        
        // Filtres appliqués
        if (!empty($filters)) {
            $pdf->SetFillColor(231, 241, 255);
            $pdf->SetTextColor(0, 86, 179);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(0, 5, 'Filtres appliqués:', 0, 1, 'L', true);
            
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(0, 0, 0);
            
            $filterTexts = [];
            if (!empty($filters['search'])) {
                $filterTexts[] = 'Recherche: "' . $filters['search'] . '"';
            }
            if (!empty($filters['categorie'])) {
                $filterTexts[] = 'Catégorie: ' . $filters['categorie'];
            }
            if (isset($filters['isPublie']) && $filters['isPublie'] !== '') {
                $filterTexts[] = 'Statut: ' . ($filters['isPublie'] == '1' ? 'Publiés' : 'Brouillons');
            }
            if (!empty($filters['dateFrom'])) {
                $filterTexts[] = 'Du: ' . (new \DateTime($filters['dateFrom']))->format('d/m/Y');
            }
            if (!empty($filters['dateTo'])) {
                $filterTexts[] = 'Au: ' . (new \DateTime($filters['dateTo']))->format('d/m/Y');
            }
            
            foreach ($filterTexts as $filterText) {
                $pdf->Cell(0, 4, '  • ' . $filterText, 0, 1, 'L');
            }
            
            $pdf->Ln(3);
        }
        
        // Tableau
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(0, 123, 255);
        $pdf->SetTextColor(255, 255, 255);
        
        // En-têtes du tableau
        $pdf->Cell(15, 7, 'ID', 1, 0, 'C', true);
        $pdf->Cell(55, 7, 'Titre', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Catégorie', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Auteur', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'Date', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'Statut', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Événement', 1, 0, 'C', true);
        $pdf->Cell(50, 7, 'Résumé', 1, 1, 'C', true);
        
        // Données
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(0, 0, 0);
        
        $fill = false;
        foreach ($blogs as $blog) {
            $pdf->SetFillColor($fill ? 248 : 255, $fill ? 249 : 255, $fill ? 250 : 255);
            
            // ID
            $pdf->MultiCell(15, 5, '#' . $blog->getId(), 1, 'C', $fill, 0, '', '', true, 0, false, true, 5);
            
            // Titre
            $titre = mb_strlen($blog->getTitre()) > 40 ? mb_substr($blog->getTitre(), 0, 37) . '...' : $blog->getTitre();
            $pdf->MultiCell(55, 5, $titre, 1, 'L', $fill, 0, '', '', true, 0, false, true, 5);
            
            // Catégorie
            $pdf->MultiCell(30, 5, $blog->getCategorie(), 1, 'C', $fill, 0, '', '', true, 0, false, true, 5);
            
            // Auteur
            $auteurNom = $blog->getAuteur() ? $blog->getAuteur()->getPrenom() . ' ' . $blog->getAuteur()->getNom() : '-';
            $pdf->MultiCell(35, 5, $auteurNom, 1, 'L', $fill, 0, '', '', true, 0, false, true, 5);
            
            // Date
            $date = $blog->getDatePublication() ? $blog->getDatePublication()->format('d/m/Y') : '-';
            $pdf->MultiCell(25, 5, $date, 1, 'C', $fill, 0, '', '', true, 0, false, true, 5);
            
            // Statut
            $statut = $blog->isPublie() ? 'Publié' : 'Brouillon';
            $pdf->MultiCell(25, 5, $statut, 1, 'C', $fill, 0, '', '', true, 0, false, true, 5);
            
            // Événement
            $evenementTitre = $blog->getEvenement() ? $blog->getEvenement()->getTitre() : '-';
            if (mb_strlen($evenementTitre) > 20) {
                $evenementTitre = mb_substr($evenementTitre, 0, 17) . '...';
            }
            $pdf->MultiCell(35, 5, $evenementTitre, 1, 'L', $fill, 0, '', '', true, 0, false, true, 5);
            
            // Résumé
            $resume = $blog->getResume() ?: '';
            if (mb_strlen($resume) > 80) {
                $resume = mb_substr($resume, 0, 77) . '...';
            }
            $pdf->MultiCell(50, 5, $resume, 1, 'L', $fill, 1, '', '', true, 0, false, true, 5);
            
            $fill = !$fill;
        }
        
        // Si aucun blog
        if (empty($blogs)) {
            $pdf->SetFont('helvetica', 'I', 10);
            $pdf->SetTextColor(150, 150, 150);
            $pdf->Cell(0, 20, 'Aucun blog à afficher', 1, 1, 'C');
        }
        
        // Pied de page personnalisé
        $pdf->SetY(-15);
        $pdf->SetFont('helvetica', 'I', 7);
        $pdf->SetTextColor(102, 102, 102);
        $pdf->Cell(0, 5, 'Export généré par ' . ($user->getEmail() ?: 'Système') . ' | ' . (new \DateTime())->format('d/m/Y H:i:s'), 0, 0, 'C');
        
        // Générer le PDF
        $filename = 'blogs-export-' . date('Y-m-d') . '.pdf';
        
        // Retourner le PDF en téléchargement
        return new Response(
            $pdf->Output($filename, 'S'),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]
        );
    }

    #[Route('/new', name: 'admin_blog_new')]
public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
{   
    // Vérifier que l'utilisateur est admin
    $user = $this->getUser();
    if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
        $this->addFlash('error', 'Accès réservé aux administrateurs.');
        return $this->redirectToRoute('accueil');
    }

    // Initialisation
    $blog = new Blog();
    $form = $this->createForm(BlogType::class, $blog, [
        'csrf_protection' => true,
        'csrf_field_name' => '_token_blog',
        'csrf_token_id'   => 'blog_item',
    ]);
    
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        if ($form->isValid()) {
            // Gérer l'upload de l'image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/blogs',
                        $newFilename
                    );
                    $blog->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $blog->setAuteur($user);
            $blog->setDatePublication(new \DateTime());

            $em->persist($blog);
            $em->flush();

            $this->addFlash('success', 'Blog créé avec succès !');
            return $this->redirectToRoute('admin_blog_index');
        } else {
            // Afficher les erreurs de validation
            $errors = $form->getErrors(true);
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }
    }

    return $this->render('admin/blog/new.html.twig', [
        'form' => $form->createView(),
    ]);
}
#[Route('/{id}/edit', name: 'admin_blog_edit')]
public function edit(Request $request, Blog $blog, EntityManagerInterface $em, SluggerInterface $slugger): Response
{
    // Vérifier que l'utilisateur est admin
    $user = $this->getUser();
    if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
        $this->addFlash('error', 'Accès réservé aux administrateurs.');
        return $this->redirectToRoute('accueil');
    }

    $form = $this->createForm(BlogType::class, $blog, [
        'csrf_protection' => true,
        'csrf_field_name' => '_token_blog',
        'csrf_token_id'   => 'blog_item',
    ]);
    
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        if ($form->isValid()) {
            // Gérer l'upload de la nouvelle image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/blogs',
                        $newFilename
                    );
                    
                    // Supprimer l'ancienne image
                    if ($blog->getImage()) {
                        $oldImagePath = $this->getParameter('kernel.project_dir').'/public/uploads/blogs/'.$blog->getImage();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $blog->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $em->flush();

            $this->addFlash('success', 'Blog modifié avec succès !');
            return $this->redirectToRoute('admin_blog_index');
        } else {
            // Afficher les erreurs de validation
            $errors = $form->getErrors(true);
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
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
        // Vérifier que l'utilisateur est admin
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        if ($this->isCsrfTokenValid('delete'.$blog->getId(), $request->request->get('_token'))) {
            // Supprimer l'image
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
    
    #[Route('/statistiques', name: 'admin_blog_statistiques')]
    public function statistiques(BlogRepository $blogRepository, EntityManagerInterface $em): Response
    {
        // Vérifier que l'utilisateur est admin
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        // Statistiques générales
        $totalBlogs = $blogRepository->count([]);
        $blogsPublies = $blogRepository->count(['isPublie' => true]);
        $blogsBrouillons = $blogRepository->count(['isPublie' => false]);

        // Blogs par catégorie
        $qb = $em->createQueryBuilder();
        $blogsByCategory = $qb->select('b.categorie, COUNT(b.id) as total')
            ->from(Blog::class, 'b')
            ->groupBy('b.categorie')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        // Blogs par auteur
        $qb2 = $em->createQueryBuilder();
        $blogsByAuthor = $qb2->select('CONCAT(u.prenom, \' \', u.nom) as auteur, COUNT(b.id) as total')
            ->from(Blog::class, 'b')
            ->join('b.auteur', 'u')
            ->groupBy('u.id')
            ->orderBy('total', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Blogs par mois (6 derniers mois)
        $sixMonthsAgo = (new \DateTime())->modify('-6 months');
        $qb3 = $em->createQueryBuilder();
        $blogsByMonth = $qb3->select('SUBSTRING(b.datePublication, 1, 7) as mois, COUNT(b.id) as total')
            ->from(Blog::class, 'b')
            ->where('b.datePublication >= :sixMonthsAgo')
            ->setParameter('sixMonthsAgo', $sixMonthsAgo)
            ->groupBy('mois')
            ->orderBy('mois', 'ASC')
            ->getQuery()
            ->getResult();

        // Blogs récents (5 derniers)
        $blogsRecents = $blogRepository->findBy([], ['datePublication' => 'DESC'], 5);

        // Blogs avec événements
        $qb4 = $em->createQueryBuilder();
        $blogsAvecEvenements = $qb4->select('COUNT(b.id)')
            ->from(Blog::class, 'b')
            ->where('b.evenement IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        // Moyenne de blogs par mois
        $firstBlog = $blogRepository->findOneBy([], ['datePublication' => 'ASC']);
        $moyenneParMois = 0;
        if ($firstBlog) {
            $firstDate = $firstBlog->getDatePublication();
            $now = new \DateTime();
            $interval = $firstDate->diff($now);
            $months = ($interval->y * 12) + $interval->m + 1;
            $moyenneParMois = $months > 0 ? round($totalBlogs / $months, 1) : 0;
        }

        return $this->render('admin/blog/statistiques.html.twig', [
            'totalBlogs' => $totalBlogs,
            'blogsPublies' => $blogsPublies,
            'blogsBrouillons' => $blogsBrouillons,
            'blogsByCategory' => $blogsByCategory,
            'blogsByAuthor' => $blogsByAuthor,
            'blogsByMonth' => $blogsByMonth,
            'blogsRecents' => $blogsRecents,
            'blogsAvecEvenements' => $blogsAvecEvenements,
            'moyenneParMois' => $moyenneParMois,
        ]);
    }

    #[Route('/{id}/qrcode', name: 'admin_blog_qrcode')]
    public function qrcode(Blog $blog): Response
    {
        // Vérifier que l'utilisateur est admin
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin') {
            $this->addFlash('error', 'Accès réservé aux administrateurs.');
            return $this->redirectToRoute('accueil');
        }

        // GÉNÉRER L'URL POUR VISITER LE BLOG
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $baseUrl = $request->getSchemeAndHttpHost();
        
        // Créer l'URL publique du blog
        $blogUrl = $baseUrl . '/public/blog/' . $blog->getId();

        return $this->render('admin/blog/qrcode.html.twig', [
            'blog' => $blog,
            'blogUrl' => $blogUrl,
        ]);
    }
    
    #[Route('/public/blog/{id}', name: 'blog_public_show')]
    public function publicShow(Blog $blog): Response
    {
        // Pas besoin de vérifier si l'utilisateur est admin pour la vue publique
        // Mais on vérifie si le blog est publié
        if (!$blog->isPublie()) {
            // Si non publié, afficher un message
            return $this->render('blog/not_published.html.twig', [
                'blog' => $blog,
            ]);
        }

        // Créer le template simple pour afficher le blog
        return $this->render('blog/public_show.html.twig', [
            'blog' => $blog,
        ]);
    }
    
    #[Route('/blog/{id}', name: 'blog_show')]
    public function show(Blog $blog): Response
    {
        // Vérifier que le blog est publié (sauf si l'utilisateur est admin)
        $user = $this->getUser();
        if (!$blog->isPublie() && (!$user instanceof User || $user->getRoleUtilisateur() !== 'admin')) {
            throw $this->createNotFoundException('Ce blog n\'est pas disponible.');
        }

        return $this->render('blog/show.html.twig', [
            'blog' => $blog,
        ]);
    }
}