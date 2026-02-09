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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Titre de l\'événement'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est requis']),
                    new Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Description de l\'événement'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La description est requise']),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('dateDebut', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La date de début est requise'])
                ]
            ])
            ->add('dateFin', DateTimeType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La date de fin est requise'])
                ]
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Lieu de l\'événement'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le lieu est requis']),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le lieu doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le lieu ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
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
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le type est requis'])
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
                ],
                'data' => true // Coché par défaut
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