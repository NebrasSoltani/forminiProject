<?php

namespace App\Controller;
use App\Entity\User;

use App\Entity\Formateur;
use App\Entity\Apprenant;
use App\Entity\Societe;

use App\Form\ProfileEditType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileEditType::class, $user);
        
        // Pré-remplir les champs spécifiques selon le rôle
        $roleUtilisateur = $user->getRoleUtilisateur();
        
        if ($roleUtilisateur === 'formateur' && $user->getFormateur()) {
            $formateur = $user->getFormateur();
            $form->get('specialite')->setData($formateur->getSpecialite());
            $form->get('bio')->setData($formateur->getBio());
            $form->get('experienceAnnees')->setData($formateur->getExperienceAnnees());
            $form->get('linkedin')->setData($formateur->getLinkedin());
            $form->get('portfolio')->setData($formateur->getPortfolio());
        } elseif ($roleUtilisateur === 'apprenant' && $user->getApprenant()) {
            $apprenant = $user->getApprenant();
            $form->get('genre')->setData($apprenant->getGenre());
            $form->get('etatCivil')->setData($apprenant->getEtatCivil());
            $form->get('niveauEtude')->setData($apprenant->getNiveauEtude());
            $form->get('objectif')->setData($apprenant->getObjectif());
        } elseif ($roleUtilisateur === 'societe' && $user->getSociete()) {
            $societe = $user->getSociete();
            $form->get('nomSociete')->setData($societe->getNomSociete());
            $form->get('secteur')->setData($societe->getSecteur());
            $form->get('descriptionSociete')->setData($societe->getDescription());
            $form->get('adresse')->setData($societe->getAdresse());
            $form->get('siteWeb')->setData($societe->getSiteWeb());
        }
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de la photo
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/photos',
                        $newFilename
                    );
                    $user->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
                }
            }

            // Mettre à jour les données spécifiques selon le rôle
            if ($roleUtilisateur === 'formateur') {
                $formateur = $user->getFormateur();
                if (!$formateur) {
                    $formateur = new Formateur();
                    $formateur->setUser($user);
                    $em->persist($formateur);
                }
                
                $formateur->setSpecialite($form->get('specialite')->getData());
                $formateur->setBio($form->get('bio')->getData());
                $formateur->setExperienceAnnees($form->get('experienceAnnees')->getData());
                $formateur->setLinkedin($form->get('linkedin')->getData());
                $formateur->setPortfolio($form->get('portfolio')->getData());
                
                // Gérer l'upload du CV
                $cvFile = $form->get('cv')->getData();
                if ($cvFile) {
                    $originalFilename = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$cvFile->guessExtension();

                    try {
                        $cvFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/cv',
                            $newFilename
                        );
                        $formateur->setCv($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erreur lors de l\'upload du CV.');
                    }
                }
                
            } elseif ($roleUtilisateur === 'apprenant') {
                $apprenant = $user->getApprenant();
                if (!$apprenant) {
                    $apprenant = new Apprenant();
                    $apprenant->setUser($user);
                    $em->persist($apprenant);
                }
                
                $apprenant->setGenre($form->get('genre')->getData());
                $apprenant->setEtatCivil($form->get('etatCivil')->getData());
                $apprenant->setNiveauEtude($form->get('niveauEtude')->getData());
                $apprenant->setObjectif($form->get('objectif')->getData());
                
            } elseif ($roleUtilisateur === 'societe') {
                $societe = $user->getSociete();
                if (!$societe) {
                    $societe = new Societe();
                    $societe->setUser($user);
                    $em->persist($societe);
                }
                
                $societe->setNomSociete($form->get('nomSociete')->getData());
                $societe->setSecteur($form->get('secteur')->getData());
                $societe->setDescription($form->get('descriptionSociete')->getData());
                $societe->setAdresse($form->get('adresse')->getData());
                $societe->setSiteWeb($form->get('siteWeb')->getData());
                
                // Gérer l'upload du logo
                $logoFile = $form->get('logo')->getData();
                if ($logoFile) {
                    $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();

                    try {
                        $logoFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/logos',
                            $newFilename
                        );
                        $societe->setLogo($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erreur lors de l\'upload du logo.');
                    }
                }
            }

            // Gérer le changement de mot de passe
            $newPassword = $form->get('newPassword')->getData();
            if ($newPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
            }

            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès !');
            
            // Rediriger vers le dashboard approprié
            $role = $user->getRoleUtilisateur();
            if ($role === 'apprenant') {
                return $this->redirectToRoute('apprenant_dashboard');
            } elseif ($role === 'formateur') {
                return $this->redirectToRoute('formateur_dashboard');
            } elseif ($role === 'admin') {
                return $this->redirectToRoute('admin_dashboard');
            } else {
                return $this->redirectToRoute('societe_dashboard');
            }
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
