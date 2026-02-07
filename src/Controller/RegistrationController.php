<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Formateur;
use App\Entity\Apprenant;
use App\Entity\Societe;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Ajouter ROLE_ADMIN si le rôle utilisateur est admin
            if ($user->getRoleUtilisateur() === 'admin') {
                $user->setRoles(['ROLE_ADMIN']);
            }

            // Gérer l'upload de la photo de profil
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/photos',
                        $newFilename
                    );
                    $user->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo');
                }
            }

            // Créer le profil spécifique selon le rôle utilisateur
            $roleUtilisateur = $user->getRoleUtilisateur();
            
            if ($roleUtilisateur === 'formateur') {
                $formateur = new Formateur();
                $formateur->setUser($user);
                
                // Récupérer les données du formulaire
                if ($form->has('specialite') && $form->get('specialite')->getData()) {
                    $formateur->setSpecialite($form->get('specialite')->getData());
                }
                if ($form->has('bio') && $form->get('bio')->getData()) {
                    $formateur->setBio($form->get('bio')->getData());
                }
                if ($form->has('experienceAnnees') && $form->get('experienceAnnees')->getData()) {
                    $formateur->setExperienceAnnees($form->get('experienceAnnees')->getData());
                }
                if ($form->has('linkedin') && $form->get('linkedin')->getData()) {
                    $formateur->setLinkedin($form->get('linkedin')->getData());
                }
                if ($form->has('portfolio') && $form->get('portfolio')->getData()) {
                    $formateur->setPortfolio($form->get('portfolio')->getData());
                }
                
                // Gérer l'upload du CV
                $cvFile = $form->get('cv')->getData();
                if ($cvFile) {
                    $originalFilename = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $cvFile->guessExtension();

                    try {
                        $cvFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/cv',
                            $newFilename
                        );
                        $formateur->setCv($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erreur lors de l\'upload du CV');
                    }
                }
                
                $em->persist($formateur);
            } elseif ($roleUtilisateur === 'apprenant') {
                $apprenant = new Apprenant();
                $apprenant->setUser($user);
                
                // Copier la date de naissance de User vers Apprenant si elle existe
                if ($user->getDateNaissance()) {
                    $apprenant->setDateNaissance($user->getDateNaissance());
                }
                
                // Récupérer les données du formulaire
                if ($form->has('genre') && $form->get('genre')->getData()) {
                    $apprenant->setGenre($form->get('genre')->getData());
                }
                if ($form->has('etatCivil') && $form->get('etatCivil')->getData()) {
                    $apprenant->setEtatCivil($form->get('etatCivil')->getData());
                }
                if ($form->has('objectif') && $form->get('objectif')->getData()) {
                    $apprenant->setObjectif($form->get('objectif')->getData());
                }
                
                $em->persist($apprenant);
            } elseif ($roleUtilisateur === 'societe') {
                $societe = new Societe();
                $societe->setUser($user);
                
                // Récupérer les données du formulaire
                if ($form->has('nomSociete') && $form->get('nomSociete')->getData()) {
                    $societe->setNomSociete($form->get('nomSociete')->getData());
                } else {
                    // Utiliser le nom et prénom comme nom de société par défaut
                    $societe->setNomSociete($user->getNom() . ' ' . $user->getPrenom());
                }
                
                if ($form->has('secteur') && $form->get('secteur')->getData()) {
                    $societe->setSecteur($form->get('secteur')->getData());
                }
                if ($form->has('descriptionSociete') && $form->get('descriptionSociete')->getData()) {
                    $societe->setDescription($form->get('descriptionSociete')->getData());
                }
                if ($form->has('adresse') && $form->get('adresse')->getData()) {
                    $societe->setAdresse($form->get('adresse')->getData());
                }
                if ($form->has('siteWeb') && $form->get('siteWeb')->getData()) {
                    $societe->setSiteWeb($form->get('siteWeb')->getData());
                }
                
                // Gérer l'upload du logo
                $logoFile = $form->get('logo')->getData();
                if ($logoFile) {
                    $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $logoFile->guessExtension();

                    try {
                        $logoFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/logos',
                            $newFilename
                        );
                        $societe->setLogo($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erreur lors de l\'upload du logo');
                    }
                }
                
                $em->persist($societe);
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
