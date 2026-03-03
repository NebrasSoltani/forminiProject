<?php

namespace App\Controller;

// Import des entités et repositories nécessaires
use App\Entity\Produit;
use App\Entity\Commande;
use App\Entity\CommandeItem;
use App\Entity\User;
use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Intl\Locale;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Produit as Product;
use App\Entity\Commande as OrderItem;
use App\Repository\ProduitTranslationRepository;

// Préfixe de toutes les routes de ce contrôleur
#[Route('/boutique')]
// Accès réservé aux utilisateurs connectés (ROLE_USER)
#[IsGranted('ROLE_USER')]
class BoutiqueController extends AbstractController
{
    /* ======================================================
       PAGE BOUTIQUE + RECHERCHE + FILTRE CATEGORIE
    ====================================================== */
    #[Route('/', name: 'boutique_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProduitRepository $produitRepository,
        SessionInterface $session,
        PaginatorInterface $paginator
    ): Response {

        // Récupération des paramètres GET pour recherche et filtre
        $categorieFilter = $request->query->get('categorie'); // filtre par catégorie
        $search = $request->query->get('q'); // recherche texte

        // Pagination
        $limit = 4; // produits par page
        $page = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * $limit;

        // Création d'une requête pour les produits actifs
        $qb = $produitRepository->createQueryBuilder('p')
            ->where('p.statut = :statut')
            ->setParameter('statut', 'actif');

        // 🔍 Recherche texte sur nom ou catégorie
        if ($search) {
            $qb->andWhere('p.nom LIKE :search OR p.categorie LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // 📂 Filtre catégorie
        if ($categorieFilter) {
            $qb->andWhere('p.categorie = :categorie')
               ->setParameter('categorie', $categorieFilter);
        }

        // Trier par date de création décroissante
        $qb->orderBy('p.dateCreation', 'DESC');

        // Utilisation de KnpPaginator
        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            10 // 10 produits par page
        );

        // Récupération du panier depuis la session
        $panier = $session->get('panier', []);

        // Affichage de la page boutique
        return $this->render('boutique/index.html.twig', [
            'pagination' => $pagination,
            'categorieFilter' => $categorieFilter,
            'q' => $search,
            'panier' => $panier,
        ]);
    }

    /* ======================================================
       DETAIL PRODUIT
    ====================================================== */
    #[Route('/produit/{id}', name: 'boutique_produit_show', methods: ['GET'])]
    public function show(
        Product $produit,
        ProduitRepository $produitRepository
    ): Response {

        // Récupérer uniquement des produits de la même catégorie
        $suggestions = $produitRepository->createQueryBuilder('p')
            ->where('p.statut = :statut')
            ->andWhere('p.id != :id')
            ->andWhere('p.categorie = :categorie')
            ->setParameter('statut', 'disponible')
            ->setParameter('id', $produit->getId())
            ->setParameter('categorie', $produit->getCategorie())
            ->orderBy('p.dateCreation', 'DESC')
            ->setMaxResults(4)
            ->getQuery()
            ->getResult();

        return $this->render('boutique/show.html.twig', [
            'produit' => $produit,
            'suggestions' => $suggestions,
        ]);
    }

    /* ======================================================
       AJOUT AU PANIER + DECRÉMENTATION DU STOCK
    ====================================================== */
    #[Route('/panier/ajouter/{id}', name: 'boutique_panier_ajouter', methods: ['GET', 'POST'])]
    public function ajouterAuPanier(
        Product $produit,
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {

        // Quantité envoyée par le formulaire, par défaut 1
        $quantite = (int)$request->request->get('quantite', 1);

        // Vérification de la validité de la quantité
        if ($quantite <= 0) {
            $this->addFlash('error', 'Quantité invalide');
            return $this->redirectToRoute('boutique_produit_show', ['id' => $produit->getId()]);
        }

        // Vérification du stock disponible
        if ($produit->getStock() < $quantite) {
            $this->addFlash('error', 'Stock insuffisant');
            return $this->redirectToRoute('boutique_produit_show', ['id' => $produit->getId()]);
        }

        // Décrémenter le stock immédiatement
        $produit->setStock($produit->getStock() - $quantite);
        $em->flush();

        // Récupération du panier depuis la session
        $panier = $session->get('panier', []);
        $id = $produit->getId();

        // Si le produit est déjà dans le panier, on augmente la quantité
        if (isset($panier[$id])) {
            $panier[$id]['quantite'] += $quantite;
        } else {
            // Sinon, on ajoute un nouvel item
            $panier[$id] = [
                'nom' => $produit->getNom(),
                'prix' => $produit->getPrix(),
                'quantite' => $quantite,
                'image' => $produit->getImage(),
            ];
        }

        // Mise à jour du panier en session
        $session->set('panier', $panier);

        $this->addFlash('success', 'Produit ajouté au panier et stock mis à jour !');

        return $this->redirectToRoute('boutique_panier');
    }

    /* ======================================================
       PAGE PANIER
    ====================================================== */
    #[Route('/panier', name: 'boutique_panier', methods: ['GET'])]
    public function panier(
        SessionInterface $session,
        ProduitRepository $produitRepository
    ): Response {

        $panier = $session->get('panier', []);
        $panierDetails = [];
        $total = 0;

        // On parcourt le panier pour récupérer les infos complètes des produits
        foreach ($panier as $id => $item) {
            $produit = $produitRepository->find($id);

            if ($produit) {
                $sousTotal = $produit->getPrix() * $item['quantite'];

                $panierDetails[] = [
                    'produit' => $produit,
                    'quantite' => $item['quantite'],
                    'sousTotal' => $sousTotal,
                ];

                $total += $sousTotal;
            }
        }

        return $this->render('boutique/panier.html.twig', [
            'panier' => $panierDetails,
            'total' => $total,
        ]);
    }

    /* ======================================================
       RETIRER DU PANIER + RESTITUTION DU STOCK
    ====================================================== */
    #[Route('/panier/retirer/{id}', name: 'boutique_panier_retirer')]
    public function retirerDuPanier(
        int $id,
        SessionInterface $session,
        ProduitRepository $produitRepository,
        EntityManagerInterface $em
    ): Response {

        $panier = $session->get('panier', []);

        if (isset($panier[$id])) {
            $quantite = $panier[$id]['quantite'];
            $produit = $produitRepository->find($id);

            if ($produit) {
                // Restaurer le stock
                $produit->setStock($produit->getStock() + $quantite);
                $em->flush();
            }

            // Retirer du panier
            unset($panier[$id]);
        }

        $session->set('panier', $panier);

        return $this->redirectToRoute('boutique_panier');
    }

    /* ======================================================
       COMMANDER
    ====================================================== */
    #[Route('/commander', name: 'boutique_commander', methods: ['POST'])]
    public function commander(
        Request $request,
        SessionInterface $session,
        ProduitRepository $produitRepository,
        EntityManagerInterface $em
    ): Response {

        $panier = $session->get('panier', []);

        if (!$panier) {
            return $this->redirectToRoute('boutique_panier');
        }

        // Création de la commande
        $commande = new Commande();
        $commande->setUtilisateur($this->getUser());
        $commande->setAdresseLivraison($request->request->get('adresse'));
        $commande->setTelephone($request->request->get('telephone'));

        // Ajout des items à la commande
        foreach ($panier as $id => $item) {
            $produit = $produitRepository->find($id);

            if ($produit && $produit->getStock() >= 0) { // stock déjà décrémenté
                $commandeItem = new OrderItem();
                $commandeItem->setProduit($produit);
                $commandeItem->setNomProduit($produit->getNom());
                $commandeItem->setQuantite($item['quantite']);
                $commandeItem->setPrixUnitaire($produit->getPrix());

                $commande->addItem($commandeItem);
            }
        }

        $commande->calculerTotal(); // méthode personnalisée pour calculer le total

        // Enregistrement en base
        $em->persist($commande);
        $em->flush();

        // On vide le panier
        $session->remove('panier');

        return $this->redirectToRoute('boutique_mes_commandes');
    }

    /* ======================================================
       MES COMMANDES
    ====================================================== */
    #[Route('/mes-commandes', name: 'boutique_mes_commandes', methods: ['GET'])]
    public function mesCommandes(CommandeRepository $commandeRepository): Response
    {
        $commandes = $commandeRepository->findByUser($this->getUser());

        return $this->render('boutique/mes_commandes.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    /* ======================================================
       DETAIL COMMANDE
    ====================================================== */
    #[Route('/commande/{id}', name: 'boutique_commande_detail', methods: ['GET'])]
    public function commandeDetail(Commande $commande): Response
    {
        // Vérification que l'utilisateur est bien le propriétaire de la commande
        if ($commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('boutique/commande_detail.html.twig', [
            'commande' => $commande,
        ]);
    }
}



