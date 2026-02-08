<?php

namespace App\Controller;

use App\Entity\Domaine;
use App\Form\DomaineType;
use App\Repository\DomaineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/domaines')]
#[IsGranted('ROLE_ADMIN')]
class DomaineController extends AbstractController
{
    #[Route('/', name: 'domaine_index', methods: ['GET'])]
    public function index(DomaineRepository $repo): Response
    {
        return $this->render('admin/domaine/index.html.twig', [
            'domaines' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'domaine_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $domaine = new Domaine();
        $form = $this->createForm(DomaineType::class, $domaine);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($domaine);
            $em->flush();

            return $this->redirectToRoute('domaine_index');
        }

        return $this->render('admin/domaine/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'domaine_edit')]
    public function edit(
        Domaine $domaine,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(DomaineType::class, $domaine);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('domaine_index');
        }

        return $this->render('admin/domaine/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'domaine_delete')]
    public function delete(
        Domaine $domaine,
        EntityManagerInterface $em
    ): Response {
        $em->remove($domaine);
        $em->flush();

        return $this->redirectToRoute('domaine_index');
    }
}
