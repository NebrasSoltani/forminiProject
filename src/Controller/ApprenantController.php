<?php

namespace App\Controller;

use App\Entity\Apprenant;
use App\Form\ApprenantType;
use App\Repository\ApprenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/apprenant')]
final class ApprenantController extends AbstractController
{
    #[Route('/listApprenant', name: 'apprenant_index')]
    public function listApprenants(ApprenantRepository $repo): Response
    {
        $apprenants = $repo->findAll();
        return $this->render('apprenant/list.html.twig', ['apprenants'=>$apprenants]);
    }
   /* #[Route('/new', name: 'apprenant_new', methods: ['GET','POST'])]
public function new(Request $request, EntityManagerInterface $em): Response
{
    $apprenant = new Apprenant();
    $form = $this->createForm(ApprenantType::class, $apprenant);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $apprenant->setPassword(password_hash($apprenant->getPassword(), PASSWORD_BCRYPT));

        $em->persist($apprenant);
        $em->flush();

        return $this->redirectToRoute('apprenant_index');
    }

    return $this->render('apprenant/new.html.twig', [
        'form' => $form->createView(),
    ]);
    }

    #[Route('/{id}/edit', name: 'apprenant_edit', methods: ['GET','POST'])]
    // src/Controller/ApprenantController.php

public function edit(Request $request, Apprenant $apprenant, UserPasswordHasherInterface $passwordHasher,
    EntityManagerInterface $em): Response
{
    $form = $this->createForm(ApprenantType::class, $apprenant);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $newPassword = $form->get('password')->getData();

        if (!empty($newPassword)) {
            $hashedPassword = $passwordHasher->hashPassword($apprenant, $newPassword);
            $apprenant->setPassword($hashedPassword);
        }

        $em->flush();

        $this->addFlash('success', 'Apprenant mis à jour avec succès !');
        return $this->redirectToRoute('apprenant_index');
    }

    return $this->render('apprenant/edit.html.twig', [
        'form' => $form->createView(),
    ]);
}

    #[Route('/{id}/delete', name: 'apprenant_delete', methods: ['POST'])]
#[Route('/{id}/delete', name: 'apprenant_delete', methods: ['POST'])]
public function delete(Apprenant $apprenant, Request $request, EntityManagerInterface $em): Response
{
    if ($this->isCsrfTokenValid('delete'.$apprenant->getId(), $request->request->get('_token'))) {
        $em->remove($apprenant);
        $em->flush();
    }

    return $this->redirectToRoute('apprenant_index');
}*/
}
