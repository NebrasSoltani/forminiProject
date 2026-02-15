<?php

namespace App\Controller;

use App\Entity\Formateur;
use App\Entity\User;
use App\Form\FormateurAdminType;
use App\Repository\FormateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/formateurs')]
#[IsGranted('ROLE_ADMIN')]
class AdminFormateurController extends AbstractController
{
    #[Route('/', name: 'admin_formateur_index', methods: ['GET'])]
    public function index(Request $request, FormateurRepository $formateurRepository): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $specialite = $request->query->get('specialite', '');
        $experienceMin = $request->query->get('experienceMin', '');
        $experienceMax = $request->query->get('experienceMax', '');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = (int) $request->query->get('limit', 10);
        if ($limit <= 0) {
            $limit = 10;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $qb = $formateurRepository->createQueryBuilder('f')
            ->join('f.user', 'u')
            ->andWhere('u.roleUtilisateur = :role')
            ->setParameter('role', 'formateur')
            ->orderBy('u.id', 'DESC');

        if ($q !== '') {
            $qb
                ->andWhere('u.email LIKE :q OR u.nom LIKE :q OR u.prenom LIKE :q OR f.specialite LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        if ($specialite !== '') {
            $qb
                ->andWhere('f.specialite = :specialite')
                ->setParameter('specialite', $specialite);
        }

        if ($experienceMin !== '') {
            $qb
                ->andWhere('f.experienceAnnees >= :experienceMin')
                ->setParameter('experienceMin', (int) $experienceMin);
        }

        if ($experienceMax !== '') {
            $qb
                ->andWhere('f.experienceAnnees <= :experienceMax')
                ->setParameter('experienceMax', (int) $experienceMax);
        }

       

        $query = $qb->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($query, true);
        $total = count($paginator);
        $totalPages = (int) max(1, (int) ceil($total / $limit));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        return $this->render('admin/formateur/index.html.twig', [
            'formateurs' => $paginator,
            'q' => $q,
            'specialite' => $specialite,
            'experienceMin' => $experienceMin,
            'experienceMax' => $experienceMax,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/new', name: 'admin_formateur_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        $user = new User();
        $user->setRoleUtilisateur('formateur');
        $user->setRoles(['ROLE_USER']);

        $formateur = new Formateur();
        $formateur->setUser($user);

        $form = $this->createForm(FormateurAdminType::class, $formateur, [
            'is_edit' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            
            // Ensure password is always set for new users
            if (empty($plainPassword)) {
                // Generate a default password if none provided
                $plainPassword = 'ChangeMe123!';
                $this->addFlash('warning', 'Un mot de passe par défaut a été généré. Le formateur devra le changer lors de sa première connexion.');
            }
            
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

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

            $em->persist($user);
            $em->persist($formateur);
            $em->flush();

            $this->addFlash('success', 'Le formateur a été créé avec succès !');

            return $this->redirectToRoute('admin_formateur_index');
        }

        return $this->render('admin/formateur/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_formateur_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function show(Formateur $formateur): Response
    {
        return $this->render('admin/formateur/show.html.twig', [
            'formateur' => $formateur,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_formateur_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(
        Request $request,
        Formateur $formateur,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(FormateurAdminType::class, $formateur, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $formateur->getUser();

            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            // Gérer l'upload de la photo de profil
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                // Supprimer l'ancienne photo si elle existe
                $oldPhoto = $user->getPhoto();
                if ($oldPhoto) {
                    $oldPhotoPath = $this->getParameter('kernel.project_dir') . '/public/uploads/photos/' . $oldPhoto;
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }

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

            // Gérer l'upload du CV
            $cvFile = $form->get('cv')->getData();
            if ($cvFile) {
                // Supprimer l'ancien CV s'il existe
                $oldCv = $formateur->getCv();
                if ($oldCv) {
                    $oldCvPath = $this->getParameter('kernel.project_dir') . '/public/uploads/cv/' . $oldCv;
                    if (file_exists($oldCvPath)) {
                        unlink($oldCvPath);
                    }
                }

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

            $em->flush();

            // Add success flash message
            $this->addFlash('success', 'Les informations du formateur ont été mises à jour avec succès !');

            return $this->redirectToRoute('admin_formateur_show', ['id' => $formateur->getId()]);
        }

        // Handle form validation errors
        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            
            $this->addFlash('error', 'Erreur de validation : ' . implode(', ', $errors));
        }

        return $this->render('admin/formateur/edit.html.twig', [
            'form' => $form,
            'formateur' => $formateur,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_formateur_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(Request $request, Formateur $formateur, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$formateur->getId(), $request->request->get('_token'))) {
            $user = $formateur->getUser();

            $em->remove($formateur);
            $em->remove($user);
            $em->flush();
        }

        return $this->redirectToRoute('admin_formateur_index');
    }
}
