<?php

namespace App\Form;

use App\Entity\Formation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // 1) Informations générales
            ->add('titre', TextType::class, [
                'label' => 'Titre de la formation',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Développement Web avec Symfony'],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire']),
                    new Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Développement Web' => 'Développement Web',
                    'Design' => 'Design',
                    'Marketing Digital' => 'Marketing Digital',
                    'Business' => 'Business',
                    'Langues' => 'Langues',
                    'Sciences' => 'Sciences',
                    'Autre' => 'Autre',
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(['message' => 'La catégorie est obligatoire'])
                ]
            ])
            ->add('niveau', ChoiceType::class, [
                'label' => 'Niveau',
                'choices' => [
                    'Débutant' => 'debutant',
                    'Intermédiaire' => 'intermediaire',
                    'Avancé' => 'avance',
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(['message' => 'Le niveau est obligatoire'])
                ]
            ])
            ->add('langue', ChoiceType::class, [
                'label' => 'Langue',
                'choices' => [
                    'Français' => 'Français',
                    'Arabe' => 'Arabe',
                    'Anglais' => 'Anglais',
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(['message' => 'La langue est obligatoire'])
                ]
            ])
            ->add('descriptionCourte', TextareaType::class, [
                'label' => 'Description courte',
                'attr' => ['class' => 'form-control', 'rows' => 3, 'maxlength' => 500],
                'constraints' => [
                    new NotBlank(['message' => 'La description courte est obligatoire']),
                    new Length([
                        'min' => 20,
                        'max' => 500,
                        'minMessage' => 'La description courte doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La description courte ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('descriptionDetaillee', TextareaType::class, [
                'label' => 'Description détaillée',
                'attr' => ['class' => 'form-control', 'rows' => 8],
                'constraints' => [
                    new NotBlank(['message' => 'La description détaillée est obligatoire']),
                    new Length([
                        'min' => 50,
                        'max' => 5000,
                        'minMessage' => 'La description détaillée doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La description détaillée ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])

            // 2) Contenu pédagogique
            ->add('objectifsPedagogiques', TextareaType::class, [
                'label' => 'Objectifs pédagogiques',
                'attr' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'Ce que les apprenants vont apprendre...'],
                'constraints' => [
                    new NotBlank(['message' => 'Les objectifs pédagogiques sont obligatoires']),
                    new Length([
                        'min' => 20,
                        'max' => 2000,
                        'minMessage' => 'Les objectifs doivent contenir au moins {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('prerequis', TextareaType::class, [
                'label' => 'Prérequis',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Compétences nécessaires avant de commencer...'],
                'constraints' => [
                    new Length(['max' => 1000])
                ]
            ])
            ->add('programme', TextareaType::class, [
                'label' => 'Programme détaillé',
                'attr' => ['class' => 'form-control', 'rows' => 8, 'placeholder' => 'Plan détaillé du contenu...'],
                'constraints' => [
                    new NotBlank(['message' => 'Le programme est obligatoire']),
                    new Length([
                        'min' => 30,
                        'max' => 3000,
                        'minMessage' => 'Le programme doit contenir au moins {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée totale (en heures)',
                'attr' => ['class' => 'form-control', 'min' => 1],
                'constraints' => [
                    new NotBlank(['message' => 'La durée est obligatoire']),
                    new Range([
                        'min' => 1,
                        'max' => 1000,
                        'notInRangeMessage' => 'La durée doit être entre {{ min }} et {{ max }} heures'
                    ])
                ]
            ])
            ->add('nombreLecons', IntegerType::class, [
                'label' => 'Nombre de leçons',
                'attr' => ['class' => 'form-control', 'min' => 1],
                'constraints' => [
                    new NotBlank(['message' => 'Le nombre de leçons est obligatoire']),
                    new Range([
                        'min' => 1,
                        'max' => 500,
                        'notInRangeMessage' => 'Le nombre de leçons doit être entre {{ min }} et {{ max }}'
                    ])
                ]
            ])

            // 3) Format
            ->add('format', ChoiceType::class, [
                'label' => 'Format de la formation',
                'choices' => [
                    'Vidéos enregistrées' => 'videos_enregistrees',
                    'Cours en direct (Live)' => 'live',
                    'Présentiel' => 'presentiel',
                    'Mixte' => 'mixte',
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(['message' => 'Le format est obligatoire'])
                ]
            ])
            ->add('dateDebut', DateTimeType::class, [
                'label' => 'Date de début',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('planning', TextareaType::class, [
                'label' => 'Planning',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Détails du planning...'],
                'constraints' => [
                    new Length(['max' => 1000])
                ]
            ])
            ->add('lienLive', TextType::class, [
                'label' => 'Lien de la session live',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'https://...'],
                'constraints' => [
                    new Url(['message' => 'Veuillez saisir une URL valide'])
                ]
            ])
            ->add('nombreSeances', IntegerType::class, [
                'label' => 'Nombre de séances',
                'required' => false,
                'attr' => ['class' => 'form-control', 'min' => 1],
                'constraints' => [
                    new Range([
                        'min' => 1,
                        'max' => 500
                    ])
                ]
            ])

            // 4) Prix & Accès
            ->add('typeAcces', ChoiceType::class, [
                'label' => 'Type d\'accès',
                'choices' => [
                    'Gratuit' => 'gratuit',
                    'Payant' => 'payant',
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(['message' => 'Le type d\'accès est obligatoire'])
                ]
            ])
            ->add('prix', MoneyType::class, [
                'label' => 'Prix (TND)',
                'required' => false,
                'currency' => 'TND',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 999999.99
                    ])
                ]
            ])
            ->add('typeAchat', ChoiceType::class, [
                'label' => 'Type d\'achat',
                'required' => false,
                'choices' => [
                    'Accès à vie' => 'acces_vie',
                    'Accès 3 mois' => 'acces_3mois',
                    'Par séance' => 'par_seance',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('prixPromo', MoneyType::class, [
                'label' => 'Prix promotionnel (TND)',
                'required' => false,
                'currency' => 'TND',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 999999.99
                    ])
                ]
            ])
            ->add('dateFinPromo', DateTimeType::class, [
                'label' => 'Date fin promotion',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])

            // 5) Médias
            ->add('imageCouverture', FileType::class, [
                'label' => 'Image de couverture',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG)',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('videoPromo', TextType::class, [
                'label' => 'URL vidéo de présentation',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'https://youtube.com/...'],
                'constraints' => [
                    new Url(['message' => 'Veuillez saisir une URL valide'])
                ]
            ])

            // Bonus - CORRECTION ICI
            ->add('certificat', CheckboxType::class, [
                'label' => 'Certificat de fin de formation',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('hasQuiz', CheckboxType::class, [
                'label' => 'Quiz d\'évaluation',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('fichiersTelechargeables', CheckboxType::class, [
                'label' => 'Fichiers téléchargeables',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('forum', CheckboxType::class, [
                'label' => 'Forum de discussion',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
        ]);
    }
}
