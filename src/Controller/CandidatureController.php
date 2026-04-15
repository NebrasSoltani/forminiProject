<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Form\CandidatureType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_USER')]
class CandidatureController extends AbstractController
{
    #[Route('/candidature/new', name: 'candidature_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $candidature = new Candidature();

        $form = $this->createForm(CandidatureType::class, $candidature);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $cvFile = $form->get('cv')->getData();

            if ($cvFile) {
                $originalFilename = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$cvFile->guessExtension();

                $cvFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/cv',
                    $newFilename
                );

                $candidature->setCv($newFilename);
            }

            $em->persist($candidature);
            $em->flush();

            $this->addFlash('success', 'Candidature envoyée avec succès');
            return $this->redirectToRoute('candidature_new');
        }

        return $this->render('candidature/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
