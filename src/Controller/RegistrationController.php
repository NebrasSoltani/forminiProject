<?php

namespace App\Controller;

use App\Entity\Apprenant;  
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
   #[Route('/signup', name: 'app_signup')]
public function register(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): Response
{
    $user = new Apprenant();  // Apprenant par défaut
    $form = $this->createForm(RegistrationFormType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $password = $form->get('plainPassword')->getData();
        $user->setPassword($hasher->hashPassword($user, $password));

        // Par défaut roles
        $user->setRoles(['ROLE_APPRENANT']);

        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('app_login');
    }

    return $this->render('registration/signup.html.twig', [
        'form' => $form->createView()
    ]);
}

}