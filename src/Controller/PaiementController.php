<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Entity\Inscription;
use App\Repository\InscriptionRepository;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/paiement')]
#[IsGranted('ROLE_USER')]
class PaiementController extends AbstractController
{
    #[Route('/inscription/{id}', name: 'paiement_inscription', methods: ['GET'])]
    public function choixPaiement(
        int $id,
        InscriptionRepository $inscriptionRepository
    ): Response {
        $inscription = $inscriptionRepository->find($id);
        
        if (!$inscription || $inscription->getApprenant() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Vérifier que le paiement n'est pas déjà validé
        if ($inscription->getModePaiement() !== 'en_attente') {
            $this->addFlash('info', 'Le paiement pour cette formation a déjà été effectué.');
            return $this->redirectToRoute('apprenant_mes_formations');
        }

        return $this->render('paiement/choix.html.twig', [
            'inscription' => $inscription,
        ]);
    }

    #[Route('/traiter/{id}', name: 'paiement_traiter', methods: ['POST'])]
    public function traiterPaiement(
        int $id,
        Request $request,
        InscriptionRepository $inscriptionRepository,
        EntityManagerInterface $em
    ): Response {
        $inscription = $inscriptionRepository->find($id);
        
        if (!$inscription || $inscription->getApprenant() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $methodePaiement = $request->request->get('methode_paiement');
        $nomTitulaire = $request->request->get('nom_titulaire');
        $numeroTelephone = $request->request->get('numero_telephone');
        
        $montant = (float) $inscription->getMontantPaye();

        // Créer l'enregistrement de paiement
        $paiement = new Paiement();
        $paiement->setInscription($inscription);
        $paiement->setMontant($inscription->getMontantPaye());
        $paiement->setMethodePaiement($methodePaiement);
        $paiement->setNomTitulaire($nomTitulaire);
        $paiement->setNumeroTelephone($numeroTelephone);

        // Traiter selon la méthode de paiement
        if ($methodePaiement === 'especes') {
            $paiement->setStatut('en_attente');
            $inscription->setModePaiement('especes');
            $message = 'Votre demande de paiement en espèces a été enregistrée.';
        } elseif ($methodePaiement === 'virement') {
            $paiement->setStatut('en_attente');
            $inscription->setModePaiement('virement');
            $message = 'Votre demande de paiement par virement a été enregistrée.';
        } elseif ($methodePaiement === 'mobile_money') {
            $paiement->setStatut('valide');
            $paiement->setDateValidation(new \DateTime());
            $inscription->setModePaiement('mobile_money');
            $message = 'Paiement par Mobile Money validé avec succès !';
        } else {
            $this->addFlash('error', 'Méthode de paiement invalide.');
            return $this->redirectToRoute('paiement_inscription', ['id' => $id]);
        }

        $em->persist($paiement);
        $em->flush();

        $this->addFlash('success', $message);
        
        return $this->redirectToRoute('paiement_confirmation', [
            'reference' => $paiement->getReferenceTransaction(),
        ]);
    }

    #[Route('/confirmation/{reference}', name: 'paiement_confirmation', methods: ['GET'])]
    public function confirmation(
        string $reference,
        PaiementRepository $paiementRepository
    ): Response {
        $paiement = $paiementRepository->findByReference($reference);
        
        if (!$paiement || $paiement->getInscription()->getApprenant() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }

        return $this->render('paiement/confirmation.html.twig', [
            'paiement' => $paiement,
        ]);
    }

    #[Route('/annuler/{id}', name: 'paiement_annuler', methods: ['POST'])]
    public function annuler(
        int $id,
        InscriptionRepository $inscriptionRepository,
        EntityManagerInterface $em
    ): Response {
        $inscription = $inscriptionRepository->find($id);
        
        if (!$inscription || $inscription->getApprenant() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Supprimer l'inscription si le paiement n'est pas validé
        if ($inscription->getModePaiement() === 'en_attente') {
            $em->remove($inscription);
            $em->flush();
            $this->addFlash('info', 'Votre inscription a été annulée.');
        }

        return $this->redirectToRoute('apprenant_formations_index');
    }
}
