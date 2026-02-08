<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\Gouvernorat;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\LessThan;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire']),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\-]+$/',
                        'message' => 'Le nom ne peut contenir que des lettres, espaces et tirets'
                    ])
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom est obligatoire']),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\-]+$/',
                        'message' => 'Le prénom ne peut contenir que des lettres, espaces et tirets'
                    ])
                ]
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'constraints' => [
                    new NotBlank(['message' => 'Le téléphone est obligatoire']),
                    new Regex([
                        'pattern' => '/^[0-9\s\+\-\.\(\)]{8,20}$/',
                        'message' => 'Format de téléphone invalide (8-20 chiffres)'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(['message' => 'L\'email est obligatoire']),
                    new Email(['message' => 'Email invalide'])
                ]
            ])
             ->add('gouvernorat', ChoiceType::class, [
        'label' => 'Gouvernorat',
        'choices' => array_combine(
            array_map(fn(Gouvernorat $g) => $g->name, Gouvernorat::cases()), 
            Gouvernorat::cases()  
        ),
        'placeholder' => 'Choisissez un gouvernorat',
        'constraints' => [
            new NotBlank(['message' => 'Le gouvernorat est obligatoire'])
        ],
    ])

            ->add('dateNaissance', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de naissance',
                'constraints' => [
                    new NotBlank(['message' => 'La date de naissance est obligatoire']),
                    new LessThan([
                        'value' => 'today',
                        'message' => 'La date de naissance doit être dans le passé'
                    ])
                ]
            ])
            ->add('profession', TextType::class, [
                'label' => 'Profession',
                'constraints' => [
                    new NotBlank(['message' => 'La profession est obligatoire']),
                    new Length(['max' => 100])
                ]
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG, GIF)',
                        'maxSizeMessage' => 'La taille maximale est de 2 Mo'
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('roleUtilisateur', ChoiceType::class, [
                'label' => 'Type de compte',
                'choices' => [
                    'Apprenant' => 'apprenant',
                    'Formateur' => 'formateur',
                    'Société' => 'societe',
                    'Administrateur' => 'admin',
                ],
                'expanded' => false,
                'placeholder' => 'Choisissez votre rôle...',
                'constraints' => [
                    new NotBlank(['message' => 'Le rôle est obligatoire'])
                ],
                'attr' => ['id' => 'roleUtilisateur']
            ])
            
            // Champs spécifiques pour Formateur
            ->add('specialite', TextType::class, [
                'label' => 'Spécialité',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biographie',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('experienceAnnees', IntegerType::class, [
                'label' => 'Années d\'expérience',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('linkedin', UrlType::class, [
                'label' => 'Profil LinkedIn',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'https://linkedin.com/in/...']
            ])
            ->add('portfolio', UrlType::class, [
                'label' => 'Portfolio',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'https://...']
            ])
            ->add('cv', FileType::class, [
                'label' => 'CV (PDF)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier PDF'
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            
            // Champs spécifiques pour Apprenant
            ->add('genre', ChoiceType::class, [
                'label' => 'Genre',
                'mapped' => false,
                'required' => false,
                'choices' => [
                    'Homme' => 'homme',
                    'Femme' => 'femme',
                    'Autre' => 'autre'
                ],
                'placeholder' => 'Choisissez...',
                'attr' => ['class' => 'form-control']
            ])
            ->add('etatCivil', ChoiceType::class, [
                'label' => 'État civil',
                'mapped' => false,
                'required' => false,
                'choices' => [
                    'Célibataire' => 'celibataire',
                    'Marié(e)' => 'marie',
                    'Divorcé(e)' => 'divorce',
                    'Veuf(ve)' => 'veuf'
                ],
                'placeholder' => 'Choisissez...',
                'attr' => ['class' => 'form-control']
            ])
            ->add('objectif', TextareaType::class, [
                'label' => 'Objectifs d\'apprentissage',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Décrivez vos objectifs...']
            ])
            
            // Champs spécifiques pour Société
            ->add('nomSociete', TextType::class, [
                'label' => 'Nom de la société',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('secteur', TextType::class, [
                'label' => 'Secteur d\'activité',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('descriptionSociete', TextareaType::class, [
                'label' => 'Description de la société',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('adresse', TextareaType::class, [
                'label' => 'Adresse',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 2]
            ])
            ->add('siteWeb', UrlType::class, [
                'label' => 'Site web',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'https://...']
            ])
            ->add('logo', FileType::class, [
                'label' => 'Logo de la société',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide'
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmer mot de passe'],
                'invalid_message' => 'Les mots de passe doivent être identiques',
                'constraints' => [
                    new NotBlank(['message' => 'Le mot de passe est obligatoire']),
                    new Length([
                        'min' => 6,
                        'max' => 4096,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères'
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                        'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre'
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $OptionsResolver): void
    {
        $OptionsResolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
