<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/commandes')]
#[IsGranted('ROLE_ADMIN')]
class AdminCommandeController extends AbstractController
{
    #[Route('/', name: 'admin_commandes', methods: ['GET'])]
    public function index(CommandeRepository $commandeRepository): Response
    {
        $commandes = $commandeRepository->findBy([], ['dateCommande' => 'DESC']);

        return $this->render('admin/commandes/index.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/{id}', name: 'admin_commande_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Commande $commande): Response
    {
        return $this->render('admin/commandes/detail.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/{id}/statut', name: 'admin_commande_update_statut', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateStatut(Commande $commande, Request $request, EntityManagerInterface $em): Response
    {
        $token = $request->request->get('_token');
        
        if (!$this->isCsrfTokenValid('update_statut' . $commande->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_commande_detail', ['id' => $commande->getId()]);
        }
        
        $newStatut = $request->request->get('statut');
        
        if (in_array($newStatut, ['en_attente', 'confirmee', 'en_cours', 'livree', 'annulee'])) {
            $commande->setStatut($newStatut);
            $em->flush();
            
            $this->addFlash('success', 'Statut de la commande mis à jour avec succès');
        } else {
            $this->addFlash('error', 'Statut invalide');
        }

        return $this->redirectToRoute('admin_commande_detail', ['id' => $commande->getId()]);
    }
}
