<?php

namespace App\Controller;

use App\Entity\Apprenant;
use App\Entity\User;
use App\Form\ApprenantAdminType;
use App\Repository\ApprenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/apprenants')]
#[IsGranted('ROLE_ADMIN')]
class AdminApprenantController extends AbstractController
{
    #[Route('/', name: 'admin_apprenant_index', methods: ['GET'])]
    public function index(Request $request, ApprenantRepository $apprenantRepository): Response
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

        $qb = $apprenantRepository->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->andWhere('u.roleUtilisateur = :role')
            ->setParameter('role', 'apprenant')
            ->orderBy('u.id', 'DESC');

        if ($q !== '') {
            $qb
                ->andWhere('u.email LIKE :q OR u.nom LIKE :q OR u.prenom LIKE :q')
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

        return $this->render('admin/apprenant/index.html.twig', [
            'apprenants' => $paginator,
            'q' => $q,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/new', name: 'admin_apprenant_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $user->setRoleUtilisateur('apprenant');
        $user->setRoles(['ROLE_USER']);

        $apprenant = new Apprenant();
        $apprenant->setUser($user);

        $form = $this->createForm(ApprenantAdminType::class, $apprenant, [
            'is_edit' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $user->setNiveauEtude($apprenant->getNiveauEtude());
            $apprenant->setDateNaissance($user->getDateNaissance());

            $em->persist($user);
            $em->persist($apprenant);
            $em->flush();

            return $this->redirectToRoute('admin_apprenant_index');
        }

        return $this->render('admin/apprenant/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_apprenant_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function show(Apprenant $apprenant): Response
    {
        return $this->render('admin/apprenant/show.html.twig', [
            'apprenant' => $apprenant,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_apprenant_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(
        Request $request,
        Apprenant $apprenant,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $form = $this->createForm(ApprenantAdminType::class, $apprenant, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $apprenant->getUser();

            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $user->setNiveauEtude($apprenant->getNiveauEtude());
            $apprenant->setDateNaissance($user->getDateNaissance());

            $em->flush();

            return $this->redirectToRoute('admin_apprenant_index');
        }

        return $this->render('admin/apprenant/edit.html.twig', [
            'form' => $form,
            'apprenant' => $apprenant,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_apprenant_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(Request $request, Apprenant $apprenant, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$apprenant->getId(), $request->request->get('_token'))) {
            $user = $apprenant->getUser();

            $em->remove($apprenant);
            $em->remove($user);
            $em->flush();
        }

        return $this->redirectToRoute('admin_apprenant_index');
    }
}
