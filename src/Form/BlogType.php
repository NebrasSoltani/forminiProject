<?php

namespace App\Form;

use App\Entity\Blog;
use App\Entity\Evenement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class BlogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'required' => false, // ← Désactiver required HTML5
                'empty_data' => '',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Titre du blog'
                ]
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'required' => false, // ← Désactiver required HTML5
                'empty_data' => '',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 10,
                    'placeholder' => 'Contenu du blog'
                ]
            ])
            ->add('resume', TextareaType::class, [
                'label' => 'Résumé',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Résumé court du blog (optionnel)',
                    'maxlength' => 500
                ]
            ])
            ->add('categorie', TextType::class, [
                'label' => 'Catégorie',
                'required' => false, // ← Désactiver required HTML5
                'empty_data' => '',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Catégorie du blog'
                ]
            ])
            ->add('isPublie', CheckboxType::class, [
                'label' => 'Publié',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('evenement', EntityType::class, [
                'class' => Evenement::class,
                'choice_label' => 'titre',
                'label' => 'Événement associé',
                'required' => false,
                'placeholder' => '-- Sélectionnez un événement (optionnel) --',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('image', FileType::class, [
                'label' => 'Image',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG, GIF, WEBP)',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Blog::class,
        ]);
    }
}