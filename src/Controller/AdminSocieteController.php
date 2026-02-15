<?php

namespace App\Controller;

use App\Entity\Societe;
use App\Entity\User;
use App\Form\SocieteAdminType;
use App\Repository\SocieteRepository;
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

#[Route('/admin/societes')]
#[IsGranted('ROLE_ADMIN')]
class AdminSocieteController extends AbstractController
{
    #[Route('/', name: 'admin_societe_index', methods: ['GET'])]
    public function index(Request $request, SocieteRepository $societeRepository): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = (int) $request->query->get('limit', 10);
        if ($limit <= 0) {
            $limit = 10;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $qb = $societeRepository->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->andWhere('u.roleUtilisateur = :role')
            ->setParameter('role', 'societe')
            ->orderBy('u.id', 'DESC');

        if ($q !== '') {
            $qb
                ->andWhere('u.email LIKE :q OR s.nomSociete LIKE :q OR s.secteur LIKE :q')
                ->setParameter('q', '%' . $q . '%');
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

        return $this->render('admin/societe/index.html.twig', [
            'societes' => $paginator,
            'q' => $q,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/new', name: 'admin_societe_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        $user = new User();
        $user->setRoleUtilisateur('societe');
        $user->setRoles(['ROLE_USER']);

        $societe = new Societe();
        $societe->setUser($user);

        $form = $this->createForm(SocieteAdminType::class, $societe, [
            'is_edit' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            
            // Ensure password is always set for new users
            if (empty($plainPassword)) {
                // Generate a default password if none provided
                $plainPassword = 'ChangeMe123!';
                $this->addFlash('warning', 'Un mot de passe par défaut a été généré. La société devra le changer lors de sa première connexion.');
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

            $em->persist($user);
            $em->persist($societe);
            $em->flush();

            $this->addFlash('success', 'La société a été créée avec succès !');

            return $this->redirectToRoute('admin_societe_index');
        }

        return $this->render('admin/societe/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_societe_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function show(Societe $societe): Response
    {
        return $this->render('admin/societe/show.html.twig', [
            'societe' => $societe,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_societe_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(
        Request $request,
        Societe $societe,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(SocieteAdminType::class, $societe, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $societe->getUser();

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

            $em->flush();

            // Add success flash message
            $this->addFlash('success', 'Les informations de la société ont été mises à jour avec succès !');

            return $this->redirectToRoute('admin_societe_show', ['id' => $societe->getId()]);
        }

        return $this->render('admin/societe/edit.html.twig', [
            'form' => $form,
            'societe' => $societe,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_societe_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(Request $request, Societe $societe, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$societe->getId(), $request->request->get('_token'))) {
            $user = $societe->getUser();

            $em->remove($societe);
            $em->remove($user);
            $em->flush();
        }

        return $this->redirectToRoute('admin_societe_index');
    }
}
