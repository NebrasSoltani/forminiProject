<?php

// On déclare le namespace du contrôleur (emplacement logique dans le projet)
namespace App\Controller;

// On importe les classes nécessaires
use App\Entity\Commande; // l'entité Commande (table commandes dans la BDD)
use App\Repository\CommandeRepository; // le repository pour récupérer les commandes
use Doctrine\ORM\EntityManagerInterface; // pour manipuler la BDD (insert, update, delete)
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // contrôleur de base Symfony
use Symfony\Component\HttpFoundation\Request; // pour accéder aux données des requêtes (GET, POST)
use Symfony\Component\HttpFoundation\Response; // pour renvoyer une réponse HTTP
use Symfony\Component\Routing\Attribute\Route; // pour définir les routes via des attributs
use Symfony\Component\Security\Http\Attribute\IsGranted; // pour gérer les permissions

// ===== Définition du contrôleur pour la gestion des commandes côté admin =====

// On définit le préfixe de route pour toutes les actions de ce contrôleur
#[Route('/admin/commandes')]
// On s'assure que seul un utilisateur avec le rôle ROLE_ADMIN peut accéder à ce contrôleur
#[IsGranted('ROLE_ADMIN')]
class AdminCommandeController extends AbstractController
{
    // ===== Affichage de la liste des commandes avec pagination =====
    #[Route('/', name: 'admin_commandes', methods: ['GET'])]
    public function index(CommandeRepository $commandeRepository, Request $request): Response
    {
        // Récupère le numéro de page dans l'URL, par défaut 1
        $page = max(1, $request->query->getInt('page', 1)); 
        $limit = 8; // nombre de commandes affichées par page
        $offset = ($page - 1) * $limit; // calcul de l'offset pour la requête SQL

        // On compte le nombre total de commandes dans la BDD
        $total = $commandeRepository->count([]);

        // On récupère les commandes avec un tri décroissant par date
        // setFirstResult et setMaxResults servent à la pagination
        $commandes = $commandeRepository->createQueryBuilder('c')
            ->orderBy('c.dateCommande', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // On calcule le nombre total de pages pour la pagination
        $pages = (int) ceil($total / $limit);

        // On envoie les données à la vue Twig
        return $this->render('admin/commandes/index.html.twig', [
            'commandes' => $commandes,       // les commandes à afficher
            'currentPage' => $page,          // la page actuelle
            'pages' => $pages,               // le nombre total de pages
            'total' => $total,               // nombre total de commandes
        ]);
    }

    // ===== Affichage du détail d'une commande =====
    #[Route('/{id}', name: 'admin_commande_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Commande $commande): Response
    {
        // Le paramètre $commande est injecté automatiquement par Symfony
        // grâce au ParamConverter : Symfony va chercher la commande en BDD avec l'id fourni
        return $this->render('admin/commandes/detail.html.twig', [
            'commande' => $commande, // on envoie la commande à la vue
        ]);
    }

    // ===== Mise à jour du statut d'une commande =====
    #[Route('/{id}/statut', name: 'admin_commande_update_statut', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateStatut(Commande $commande, Request $request, EntityManagerInterface $em): Response
    {
        // Vérification du token CSRF pour éviter les attaques (sécurité)
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('update_statut' . $commande->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide'); // message d'erreur flash
            return $this->redirectToRoute('admin_commande_detail', ['id' => $commande->getId()]);
        }

        // Récupération du nouveau statut envoyé depuis le formulaire
        $newStatut = $request->request->get('statut');

        // Vérification que le statut est valide avant de le mettre à jour
        if (in_array($newStatut, ['en_attente', 'confirmee', 'en_cours', 'livree', 'annulee'])) {
            $commande->setStatut($newStatut); // mise à jour de l'entité
            $em->flush(); // sauvegarde en BDD

            $this->addFlash('success', 'Statut mis à jour avec succès'); // message flash succès
        } else {
            $this->addFlash('error', 'Statut invalide'); // message flash erreur
        }

        // Redirection vers la page détail de la commande
        return $this->redirectToRoute('admin_commande_detail', ['id' => $commande->getId()]);
    }
}
