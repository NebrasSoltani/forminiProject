<?php

namespace App\Form;

use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'required' => false, // ← false pour désactiver required HTML5
                'empty_data' => '',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Titre de l\'événement'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false, // ← false pour désactiver required HTML5
                'empty_data' => '',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Description de l\'événement'
                ]
            ])
            ->add('dateDebut', TextType::class, [ // ← CHANGÉ DE DateTimeType À TextType
                'label' => 'Date de début',
                'required' => false,
                'mapped' => false, // ← Important : ne pas mapper directement
                'attr' => [
                    'class' => 'form-control',
                    'style' => 'display: none;' // ← Caché car on utilise datetime-local
                ]
            ])
            ->add('dateFin', TextType::class, [ // ← CHANGÉ DE DateTimeType À TextType
                'label' => 'Date de fin',
                'required' => false,
                'mapped' => false, // ← Important : ne pas mapper directement
                'attr' => [
                    'class' => 'form-control',
                    'style' => 'display: none;' // ← Caché car on utilise datetime-local
                ]
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'required' => false, // ← false pour désactiver required HTML5
                'empty_data' => '',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Lieu de l\'événement'
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'required' => false, // ← false pour désactiver required HTML5
                'empty_data' => '',
                'placeholder' => '-- Sélectionnez un type --',
                'choices' => [
                    'Conférence' => 'Conférence',
                    'Atelier' => 'Atelier',
                    'Webinaire' => 'Webinaire',
                    'Formation' => 'Formation',
                    'Networking' => 'Networking',
                    'Séminaire' => 'Séminaire',
                    'Hackathon' => 'Hackathon',
                    'Autre' => 'Autre',
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('nombrePlaces', IntegerType::class, [
                'label' => 'Nombre de places',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Laissez vide pour illimité',
                    'min' => 1
                ]
            ])
            ->add('isActif', CheckboxType::class, [
                'label' => 'Événement actif',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
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
                        'maxSize' => '5M',
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
            'data_class' => Evenement::class,
        ]);
    }
}