<?php

namespace App\Controller;

use App\Entity\OffreStage;
use App\Entity\Candidature;
use App\Entity\User;
use App\Form\CandidatureType;
use App\Repository\OffreStageRepository;
use App\Repository\CandidatureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/stages')]
#[IsGranted('ROLE_USER')]
class ApprenantStageController extends AbstractController
{
    #[Route('/', name: 'apprenant_stages_index', methods: ['GET'])]
    public function index(Request $request, OffreStageRepository $offreStageRepository): Response
    {
        $type = $request->query->get('type');
        
        if ($type) {
            $offres = $offreStageRepository->findByType($type);
        } else {
            $offres = $offreStageRepository->findPubliees();
        }

        return $this->render('apprenant/stages/index.html.twig', [
            'offres' => $offres,
            'typeFilter' => $type,
        ]);
    }

    #[Route('/mes-candidatures', name: 'apprenant_mes_candidatures', methods: ['GET'])]
    public function mesCandidatures(CandidatureRepository $candidatureRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($user->getRoleUtilisateur() !== 'apprenant') {
            throw $this->createAccessDeniedException('Accès réservé aux apprenants');
        }

        $candidatures = $candidatureRepository->findByApprenant($user);

        return $this->render('apprenant/stages/mes_candidatures.html.twig', [
            'candidatures' => $candidatures,
        ]);
    }

    #[Route('/{id}', name: 'apprenant_stage_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(OffreStage $offre, CandidatureRepository $candidatureRepository): Response
    {
        if ($offre->getStatut() !== 'publiee') {
            throw $this->createNotFoundException('Cette offre n\'est plus disponible');
        }

        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a déjà postulé
        $hasApplied = false;
        if ($user->getRoleUtilisateur() === 'apprenant') {
            $hasApplied = $candidatureRepository->hasAlreadyApplied($user, $offre);
        }

        return $this->render('apprenant/stages/show.html.twig', [
            'offre' => $offre,
            'dejaPostule' => $hasApplied,
        ]);
    }

    #[Route('/{id}/postuler', name: 'apprenant_stage_postuler', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function postuler(
        OffreStage $offre,
        Request $request,
        EntityManagerInterface $em,
        CandidatureRepository $candidatureRepository,
        SluggerInterface $slugger
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que c'est un apprenant
        if ($user->getRoleUtilisateur() !== 'apprenant') {
            throw $this->createAccessDeniedException('Seuls les apprenants peuvent postuler');
        }

        // Vérifier si déjà postulé
        if ($candidatureRepository->hasAlreadyApplied($user, $offre)) {
            $this->addFlash('warning', 'Vous avez déjà postulé à cette offre');
            return $this->redirectToRoute('apprenant_stage_show', ['id' => $offre->getId()]);
        }

        $candidature = new Candidature();
        $candidature->setOffreStage($offre);
        $candidature->setApprenant($user);
        
        $form = $this->createForm(CandidatureType::class, $candidature);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload du CV
            $cvFile = $form->get('cv')->getData();
            if ($cvFile) {
                $originalFilename = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$cvFile->guessExtension();

                try {
                    $cvFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/cv',
                        $newFilename
                    );
                    $candidature->setCv($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement du CV');
                    return $this->redirectToRoute('apprenant_stage_postuler', ['id' => $offre->getId()]);
                }
            }

            $em->persist($candidature);
            $em->flush();

            $this->addFlash('success', 'Candidature envoyée avec succès!');
            return $this->redirectToRoute('apprenant_mes_candidatures');
        }

        return $this->render('apprenant/stages/postuler.html.twig', [
            'offre' => $offre,
            'form' => $form,
        ]);
    }
}
