<?php

namespace App\Controller;

use App\Repository\FormationRepository;
use App\Repository\InscriptionRepository;
use App\Repository\FavoriRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/formations')]
class ApprenantController extends AbstractController
{
    #[Route('/', name: 'apprenant_formations_index', methods: ['GET'])]
    public function index(Request $request, FormationRepository $formationRepository): Response
    {
        // Récupérer les filtres
        $categorie = $request->query->get('categorie');
        $niveau = $request->query->get('niveau');
        $typeAcces = $request->query->get('typeAcces');
        $search = $request->query->get('search');

        // Construire la requête avec filtres
        $qb = $formationRepository->createQueryBuilder('f')
            ->where('f.statut = :statut')
            ->setParameter('statut', 'publiee')
            ->orderBy('f.datePublication', 'DESC');

        if ($categorie) {
            $qb->andWhere('f.categorie = :categorie')
               ->setParameter('categorie', $categorie);
        }

        if ($niveau) {
            $qb->andWhere('f.niveau = :niveau')
               ->setParameter('niveau', $niveau);
        }

        if ($typeAcces) {
            $qb->andWhere('f.typeAcces = :typeAcces')
               ->setParameter('typeAcces', $typeAcces);
        }

        if ($search) {
            $qb->andWhere('f.titre LIKE :search OR f.descriptionCourte LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $formations = $qb->getQuery()->getResult();

        // Récupérer les catégories et niveaux uniques pour les filtres
        $categories = $formationRepository->createQueryBuilder('f')
            ->select('DISTINCT f.categorie')
            ->where('f.statut = :statut')
            ->setParameter('statut', 'publiee')
            ->getQuery()
            ->getScalarResult();

        $niveaux = $formationRepository->createQueryBuilder('f')
            ->select('DISTINCT f.niveau')
            ->where('f.statut = :statut')
            ->setParameter('statut', 'publiee')
            ->getQuery()
            ->getScalarResult();

        return $this->render('apprenant/formations/index.html.twig', [
            'formations' => $formations,
            'categories' => array_column($categories, 'categorie'),
            'niveaux' => array_column($niveaux, 'niveau'),
            'currentCategorie' => $categorie,
            'currentNiveau' => $niveau,
            'currentTypeAcces' => $typeAcces,
            'currentSearch' => $search,
        ]);
    }

    #[Route('/{id}', name: 'apprenant_formation_show', methods: ['GET'])]
    public function show(
        int $id, 
        FormationRepository $formationRepository,
        InscriptionRepository $inscriptionRepository,
        FavoriRepository $favoriRepository
    ): Response {
        $formation = $formationRepository->find($id);

        if (!$formation || $formation->getStatut() !== 'publiee') {
            throw $this->createNotFoundException('Formation non trouvée ou non publiée');
        }

        $inscription = null;
        $favori = null;

        if ($this->getUser()) {
            $inscription = $inscriptionRepository->findOneByApprenantAndFormation($this->getUser(), $id);
            $favori = $favoriRepository->findOneByApprenantAndFormation($this->getUser(), $id);
        }

        return $this->render('apprenant/formations/show.html.twig', [
            'formation' => $formation,
            'inscription' => $inscription,
            'favori' => $favori,
        ]);
    }

    #[Route('/categorie/{categorie}', name: 'apprenant_formations_by_category', methods: ['GET'])]
    public function byCategory(string $categorie, FormationRepository $formationRepository): Response
    {
        $formations = $formationRepository->createQueryBuilder('f')
            ->where('f.statut = :statut')
            ->andWhere('f.categorie = :categorie')
            ->setParameter('statut', 'publiee')
            ->setParameter('categorie', $categorie)
            ->orderBy('f.datePublication', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('apprenant/formations/category.html.twig', [
            'formations' => $formations,
            'categorie' => $categorie,
        ]);
    }
}