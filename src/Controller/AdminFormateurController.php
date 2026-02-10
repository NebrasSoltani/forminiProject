<?php

namespace App\Controller;

use App\Entity\Formateur;
use App\Entity\User;
use App\Form\FormateurAdminType;
use App\Repository\FormateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/formateurs')]
#[IsGranted('ROLE_ADMIN')]
class AdminFormateurController extends AbstractController
{
    #[Route('/', name: 'admin_formateur_index', methods: ['GET'])]
    public function index(Request $request, FormateurRepository $formateurRepository): Response
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
        UserPasswordHasherInterface $passwordHasher
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
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $em->persist($user);
            $em->persist($formateur);
            $em->flush();

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
        UserPasswordHasherInterface $passwordHasher
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

            $em->flush();

            return $this->redirectToRoute('admin_formateur_index');
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
