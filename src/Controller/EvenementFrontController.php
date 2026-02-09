<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\ParticipationEvenement;
use App\Repository\EvenementRepository;
use App\Repository\ParticipationEvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/evenements')]
class EvenementFrontController extends AbstractController
{
    #[Route('', name: 'apprenant_evenements_index', methods: ['GET'])]
    public function index(EvenementRepository $evenementRepository, ParticipationEvenementRepository $participationRepo): Response
    {
        $evenements = $evenementRepository->findActiveEvents();
        $user = $this->getUser();
        $idsParticipation = [];
        if ($user) {
            foreach ($evenements as $e) {
                if ($participationRepo->isParticipant($user, $e)) {
                    $idsParticipation[$e->getId()] = true;
                }
            }
        }

        return $this->render('apprenant/evenement/index.html.twig', [
            'evenements' => $evenements,
            'ids_participation' => $idsParticipation,
        ]);
    }

    #[Route('/{id}/participer', name: 'apprenant_evenement_participer', methods: ['POST'])]
    public function participer(Request $request, Evenement $evenement, EntityManagerInterface $em, ParticipationEvenementRepository $participationRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour participer.');
            return $this->redirectToRoute('app_login');
        }

        if (!$evenement->isActif()) {
            $this->addFlash('error', 'Cet événement n\'est plus disponible.');
            return $this->redirectToRoute('apprenant_evenements_index');
        }

        $now = new \DateTime();
        // On compare seulement la date sans l'heure pour permettre l'inscription le jour même
        $dateFin = clone $evenement->getDateFin();
        $dateFin->setTime(23, 59, 59);
        
        if ($evenement->getDateFin() && $dateFin < $now) {
            $this->addFlash('error', 'Les inscriptions sont closes pour cet événement (événement terminé).');
            return $this->redirectToRoute('apprenant_evenements_index');
        }

        if ($participationRepo->isParticipant($user, $evenement)) {
            $this->addFlash('info', 'Vous participez déjà à cet événement.');
            return $this->redirectToRoute('apprenant_evenements_index');
        }

        $nombrePlaces = $evenement->getNombrePlaces();
        if ($nombrePlaces !== null && $nombrePlaces <= 0) {
            $this->addFlash('error', 'Il n\'y a plus de places disponibles pour cet événement.');
            return $this->redirectToRoute('apprenant_evenements_index');
        }

        $csrf = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('participer_evenement_' . $evenement->getId(), $csrf)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('apprenant_evenements_index');
        }

        $participation = new ParticipationEvenement();
        $participation->setUser($user);
        $participation->setEvenement($evenement);
        
        // Décrémenter le nombre de places
        if ($nombrePlaces !== null) {
            $evenement->setNombrePlaces($nombrePlaces - 1);
        }

        $em->persist($participation);
        $em->flush();

        $this->addFlash('success', 'Inscription validée ! Voici votre billet.');
        return $this->redirectToRoute('apprenant_evenement_ticket', ['id' => $participation->getId()]);
    }

    #[Route('/participation/{id}/billet', name: 'apprenant_evenement_ticket', methods: ['GET'])]
    public function ticket(ParticipationEvenement $participation): Response
    {
        $user = $this->getUser();
        if (!$user || $participation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à ce billet.');
        }

        return $this->render('apprenant/evenement/ticket.html.twig', [
            'participation' => $participation,
            'evenement' => $participation->getEvenement(),
            'user' => $user,
        ]);
    }
}
