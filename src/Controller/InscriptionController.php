<?php

namespace App\Controller;

use App\Entity\Inscription;
use App\Entity\Favori;
use App\Repository\FormationRepository;
use App\Repository\InscriptionRepository;
use App\Repository\FavoriRepository;
use App\Repository\ProgressionLeconRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/apprenant')]
#[IsGranted('ROLE_USER')]
class InscriptionController extends AbstractController
{
    #[Route('/inscrire/{id}', name: 'apprenant_inscrire', methods: ['POST'])]
    public function inscrire(
        int $id,
        Request $request,
        FormationRepository $formationRepository,
        InscriptionRepository $inscriptionRepository,
        EntityManagerInterface $em
    ): Response {
        $formation = $formationRepository->find($id);
        
        if (!$formation || $formation->getStatut() !== 'publiee') {
            throw $this->createNotFoundException('Formation non trouvée');
        }

        $user = $this->getUser();

        // Vérifier si déjà inscrit
        if ($inscriptionRepository->isInscrit($user, $id)) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à cette formation');
            return $this->redirectToRoute('apprenant_formation_show', ['id' => $id]);
        }

        $inscription = new Inscription();
        $inscription->setApprenant($user);
        $inscription->setFormation($formation);

        if ($formation->getTypeAcces() === 'gratuit') {
            $inscription->setModePaiement('gratuit');
            $inscription->setMontantPaye('0.00');
            
            $em->persist($inscription);
            $em->flush();

            $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant accéder à la formation.');
            return $this->redirectToRoute('apprenant_mes_formations');
        } else {
            // Pour les formations payantes, rediriger vers le paiement
            $montant = $formation->getPrixPromo() ?? $formation->getPrix();
            $inscription->setMontantPaye($montant);
            $inscription->setModePaiement('en_attente');
            
            $em->persist($inscription);
            $em->flush();

            // Rediriger vers la page de paiement
            return $this->redirectToRoute('paiement_inscription', ['id' => $inscription->getId()]);
        }
    }

    #[Route('/mes-formations', name: 'apprenant_mes_formations', methods: ['GET'])]
    public function mesFormations(InscriptionRepository $inscriptionRepository): Response
    {
        $inscriptions = $inscriptionRepository->findByApprenant($this->getUser());

        return $this->render('apprenant/mes_formations.html.twig', [
            'inscriptions' => $inscriptions,
        ]);
    }

    #[Route('/formation/{id}/suivre', name: 'apprenant_suivre_formation', methods: ['GET'])]
    public function suivreFormation(
        int $id,
        InscriptionRepository $inscriptionRepository,
        FormationRepository $formationRepository,
        ProgressionLeconRepository $progressionLeconRepository
    ): Response {
        $formation = $formationRepository->find($id);
        $inscription = $inscriptionRepository->findOneByApprenantAndFormation($this->getUser(), $id);

        if (!$inscription) {
            $this->addFlash('error', 'Vous devez d\'abord vous inscrire à cette formation');
            return $this->redirectToRoute('apprenant_formation_show', ['id' => $id]);
        }

        // Récupérer les progressions pour savoir quelles leçons sont terminées
        $progressions = [];
        foreach ($formation->getLecons() as $lecon) {
            $progression = $progressionLeconRepository->findOneByApprenantAndLecon($this->getUser(), $lecon);
            $progressions[$lecon->getId()] = $progression ? $progression->isTerminee() : false;
        }

        return $this->render('apprenant/suivre_formation.html.twig', [
            'formation' => $formation,
            'inscription' => $inscription,
            'progressions' => $progressions,
        ]);
    }

    #[Route('/favori/{id}/toggle', name: 'apprenant_toggle_favori', methods: ['POST'])]
    public function toggleFavori(
        int $id,
        FormationRepository $formationRepository,
        FavoriRepository $favoriRepository,
        EntityManagerInterface $em
    ): Response {
        $formation = $formationRepository->find($id);
        
        if (!$formation) {
            throw $this->createNotFoundException();
        }

        $user = $this->getUser();
        $favori = $favoriRepository->findOneByApprenantAndFormation($user, $id);

        if ($favori) {
            // Retirer des favoris
            $em->remove($favori);
            $em->flush();
            $this->addFlash('success', 'Formation retirée des favoris');
        } else {
            // Ajouter aux favoris
            $favori = new Favori();
            $favori->setApprenant($user);
            $favori->setFormation($formation);
            $em->persist($favori);
            $em->flush();
            $this->addFlash('success', 'Formation ajoutée aux favoris');
        }

        return $this->redirectToRoute('apprenant_formation_show', ['id' => $id]);
    }

    #[Route('/mes-favoris', name: 'apprenant_mes_favoris', methods: ['GET'])]
    public function mesFavoris(FavoriRepository $favoriRepository): Response
    {
        $favoris = $favoriRepository->findByApprenant($this->getUser());

        return $this->render('apprenant/mes_favoris.html.twig', [
            'favoris' => $favoris,
        ]);
    }
}