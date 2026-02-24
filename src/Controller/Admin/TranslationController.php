<?php

namespace App\Controller\Admin;

use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/translations')]
class TranslationController extends AbstractController
{
    #[Route('/', name: 'admin_translations_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $connection = $em->getConnection();
        
        // Récupérer toutes les traductions des produits
        $translations = $connection->fetchAllAssociative(
            "SELECT et.*, p.id as produit_id, p.categorie, p.prix 
             FROM ext_translations et
             LEFT JOIN produit p ON et.foreign_key = p.id
             WHERE et.object_class = 'App\\\\Entity\\\\Produit'
             ORDER BY et.foreign_key, et.locale, et.field"
        );
        
        // Grouper par produit
        $grouped = [];
        foreach ($translations as $translation) {
            $productId = $translation['produit_id'];
            if (!isset($grouped[$productId])) {
                $grouped[$productId] = [
                    'id' => $productId,
                    'categorie' => $translation['categorie'],
                    'prix' => $translation['prix'],
                    'translations' => []
                ];
            }
            $grouped[$productId]['translations'][] = $translation;
        }
        
        return $this->render('admin/translations/index.html.twig', [
            'products' => $grouped
        ]);
    }
    
    #[Route('/edit/{id}', name: 'admin_translations_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $produit = $em->getRepository(Produit::class)->find($id);
        
        if (!$produit) {
            throw $this->createNotFoundException('Produit non trouvé');
        }
        
        if ($request->isMethod('POST')) {
            $locale = $request->request->get('locale');
            $field = $request->request->get('field');
            $content = $request->request->get('content');
            
            // Mettre à jour la traduction
            $produit->setTranslatableLocale($locale);
            $em->refresh($produit);
            
            if ($field === 'nom') {
                $produit->setNom($content);
            } elseif ($field === 'description') {
                $produit->setDescription($content);
            }
            
            $em->persist($produit);
            $em->flush();
            
            $this->addFlash('success', 'Traduction mise à jour avec succès');
            return $this->redirectToRoute('admin_translations_index');
        }
        
        // Récupérer les traductions existantes
        $connection = $em->getConnection();
        $translations = $connection->fetchAllAssociative(
            "SELECT * FROM ext_translations 
             WHERE object_class = 'App\\\\Entity\\\\Produit' 
             AND foreign_key = :id",
            ['id' => $id]
        );
        
        return $this->render('admin/translations/edit.html.twig', [
            'produit' => $produit,
            'translations' => $translations
        ]);
    }
}
