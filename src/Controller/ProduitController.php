<?php

namespace App\Controller;

// Import des classes nécessaires
use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

// Préfixe de route pour ce contrôleur
#[Route('/admin/produit')]
// Accès réservé aux administrateurs
#[IsGranted('ROLE_ADMIN')]
class ProduitController extends AbstractController
{
    /* ======================================================
       LISTE DES PRODUITS AVEC PAGINATION
    ====================================================== */
    #[Route('/', name: 'produit_index', methods: ['GET'])]
    public function index(Request $request, ProduitRepository $produitRepository): Response
    {
        $limit = 2; // Nombre de produits par page
        $page = max(1, (int) $request->query->get('page', 1)); // page actuelle depuis URL
        $offset = ($page - 1) * $limit; // calcul de l'offset pour la requête

        // Création de la requête pour récupérer les produits
        $qb = $produitRepository->createQueryBuilder('p')
            ->orderBy('p.dateCreation', 'DESC'); // tri par date décroissante

        // Clonage pour compter le total sans ordre
        $countQb = clone $qb;
        $countQb->resetDQLPart('orderBy');

        $total = (int) $countQb
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult(); // nombre total de produits

        $pages = max(1, (int) ceil($total / $limit)); // nombre total de pages
        $page = min($page, $pages); // si page demandée > pages disponibles
        $offset = ($page - 1) * $limit;

        // Récupération des produits paginés
        $produits = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Affichage dans la vue
        return $this->render('produit/index.html.twig', [
            'produits' => $produits,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit,
            'total' => $total,
        ]);
    }

    /* ======================================================
       CRÉER UN NOUVEAU PRODUIT
    ====================================================== */
    #[Route('/new', name: 'produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit); // Formulaire Symfony
        $form->handleRequest($request); // Traite la requête POST si soumise

        if ($form->isSubmitted() && $form->isValid()) {
            // ===== Gestion de l'image =====
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                // Nom sécurisé pour le fichier
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    // Déplacement du fichier dans le dossier public/uploads/produits
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/produits',
                        $newFilename
                    );
                    $produit->setImage($newFilename); // on associe le nom de l'image au produit
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image');
                }
            }

            $em->persist($produit); // préparation pour insertion en BDD
            $em->flush();           // insertion en base

            $this->addFlash('success', 'Produit créé avec succès!');
            return $this->redirectToRoute('produit_index');
        }

        return $this->render('produit/form.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    /* ======================================================
       AFFICHER LE DÉTAIL D’UN PRODUIT
    ====================================================== */
    #[Route('/{id}', name: 'produit_show', methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        // Symfony injecte automatiquement le produit via l’id
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }

    /* ======================================================
       MODIFIER UN PRODUIT
    ====================================================== */
    #[Route('/{id}/edit', name: 'produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image (similaire à la création)
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/produits',
                        $newFilename
                    );
                    $produit->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image');
                }
            }

            $em->flush(); // Sauvegarde les modifications en base

            $this->addFlash('success', 'Produit modifié avec succès!');
            return $this->redirectToRoute('produit_index');
        }

        return $this->render('produit/form.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    /* ======================================================
       SUPPRIMER UN PRODUIT
    ====================================================== */
    #[Route('/{id}/delete', name: 'produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        // Vérification du token CSRF pour sécurité (CSRF = Cross-Site Request Forgery)
        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
            $em->remove($produit); // suppression
            $em->flush();           // exécution en BDD
            $this->addFlash('success', 'Produit supprimé avec succès!');
        }

        return $this->redirectToRoute('produit_index');
    }
}
