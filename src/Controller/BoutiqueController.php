<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\Commande;
use App\Entity\CommandeItem;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/boutique')]
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
        SessionInterface $session
    ): Response {

        $categorieFilter = $request->query->get('categorie');
        $search = $request->query->get('q');

        $limit = 4;
        $page = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * $limit;

        $qb = $produitRepository->createQueryBuilder('p')
            ->where('p.statut = :statut')
            ->setParameter('statut', 'actif');

        // 🔍 Recherche texte
        if ($search) {
            $qb->andWhere('p.nom LIKE :search OR p.categorie LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // 📂 Filtre catégorie
        if ($categorieFilter) {
            $qb->andWhere('p.categorie = :categorie')
               ->setParameter('categorie', $categorieFilter);
        }

        $qb->orderBy('p.dateCreation', 'DESC');

        $countQb = clone $qb;
        $countQb->resetDQLPart('orderBy');

        $total = (int) $countQb
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $pages = max(1, (int) ceil($total / $limit));
        $page = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $produits = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $panier = $session->get('panier', []);

        return $this->render('boutique/index.html.twig', [
            'produits' => $produits,
            'categorieFilter' => $categorieFilter,
            'q' => $search,
            'panier' => $panier,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit,
            'total' => $total,
        ]);
    }

    /* ======================================================
       DETAIL PRODUIT
    ====================================================== */
    #[Route('/produit/{id}', name: 'boutique_produit_show', methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        return $this->render('boutique/show.html.twig', [
            'produit' => $produit,
        ]);
    }

    /* ======================================================
       AJOUT PANIER AVEC DECRÉMENTATION DU STOCK
    ====================================================== */
    #[Route('/panier/ajouter/{id}', name: 'boutique_panier_ajouter', methods: ['GET', 'POST'])]
    public function ajouterAuPanier(
        Produit $produit,
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {

        $quantite = (int)$request->request->get('quantite', 1);

        if ($quantite <= 0) {
            $this->addFlash('error', 'Quantité invalide');
            return $this->redirectToRoute('boutique_produit_show', ['id' => $produit->getId()]);
        }

        if ($produit->getStock() < $quantite) {
            $this->addFlash('error', 'Stock insuffisant');
            return $this->redirectToRoute('boutique_produit_show', ['id' => $produit->getId()]);
        }

        // Décrémenter le stock immédiatement
        $produit->setStock($produit->getStock() - $quantite);
        $em->flush();

        $panier = $session->get('panier', []);
        $id = $produit->getId();

        if (isset($panier[$id])) {
            $panier[$id]['quantite'] += $quantite;
        } else {
            $panier[$id] = [
                'nom' => $produit->getNom(),
                'prix' => $produit->getPrix(),
                'quantite' => $quantite,
                'image' => $produit->getImage(),
            ];
        }

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
       RETIRER DU PANIER AVEC RESTITUTION DU STOCK
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

        $commande = new Commande();
        $commande->setUtilisateur($this->getUser());
        $commande->setAdresseLivraison($request->request->get('adresse'));
        $commande->setTelephone($request->request->get('telephone'));

        foreach ($panier as $id => $item) {

            $produit = $produitRepository->find($id);

            if ($produit && $produit->getStock() >= 0) { // stock déjà décrémenté

                $commandeItem = new CommandeItem();
                $commandeItem->setProduit($produit);
                $commandeItem->setNomProduit($produit->getNom());
                $commandeItem->setQuantite($item['quantite']);
                $commandeItem->setPrixUnitaire($produit->getPrix());

                $commande->addItem($commandeItem);
            }
        }

        $commande->calculerTotal();

        $em->persist($commande);
        $em->flush();

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
        if ($commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('boutique/commande_detail.html.twig', [
            'commande' => $commande,
        ]);
    }
}
