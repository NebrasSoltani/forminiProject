<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;

// Classe qui définit le formulaire pour créer/modifier un Produit
class ProduitType extends AbstractType
{
    // ===== CONSTRUCTION DU FORMULAIRE =====
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // NOM DU PRODUIT
            ->add('nom', TextType::class, [
                'label' => 'Nom du produit',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom du produit est obligatoire']), // champ obligatoire
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            
            // CATÉGORIE
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Informatique' => 'Informatique',
                    'Outils intelligents' => 'Outils intelligents',
                    'Scientifique' => 'Scientifique',
                    'Accessoires' => 'Accessoires',
                ],
                'placeholder' => 'Choisir une catégorie',
                'constraints' => [
                    new NotBlank(['message' => 'La catégorie est obligatoire']) // champ obligatoire
                ]
            ])
            
            // DESCRIPTION
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false, // optionnel
                'attr' => ['rows' => 5], // hauteur du textarea
                'constraints' => [
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            
            // PRIX
            ->add('prix', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'dt', // devise Dinar tunisien
                'constraints' => [
                    new NotBlank(['message' => 'Le prix est obligatoire']),
                    new Positive(['message' => 'Le prix doit être positif']), // prix > 0
                    new Range([
                        'min' => 0.01,
                        'max' => 999999.99,
                        'notInRangeMessage' => 'Le prix doit être entre {{ min }} et {{ max }}'
                    ])
                ]
            ])
            
            // STOCK
            ->add('stock', NumberType::class, [
                'label' => 'Stock disponible',
                // ici tu pourrais ajouter des contraintes si tu veux (ex: >= 0)
            ])
            
            // STATUT
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => 'actif',
                    'Inactif' => 'inactif',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le statut est obligatoire'])
                ]
            ])
            
            // IMAGE DU PRODUIT
            ->add('imageFile', FileType::class, [
                'label' => 'Image du produit',
                'mapped' => false, // pas directement liée à la propriété image de l'entité
                'required' => false, // optionnel
                'constraints' => [
                    new File([
                        'maxSize' => '2M', // taille maximale 2 Mo
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG, GIF, WEBP)',
                    ])
                ],
            ]);
    }

    // ===== CONFIGURATION DES OPTIONS DU FORMULAIRE =====
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class, // le formulaire travaille sur l'entité Produit
        ]);
    }
}
