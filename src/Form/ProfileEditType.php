<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;

class ProfileEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => true,
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('governorat', TextType::class, [
                'label' => 'Gouvernorat',
                'required' => false,
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('profession', TextType::class, [
                'label' => 'Profession',
                'required' => false,
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
                    ])
                ],
            ])
            
            // Champs spécifiques Formateur
            ->add('specialite', TextType::class, [
                'label' => 'Spécialité',
                'mapped' => false,
                'required' => false,
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biographie',
                'mapped' => false,
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('experienceAnnees', IntegerType::class, [
                'label' => 'Années d\'expérience',
                'mapped' => false,
                'required' => false,
            ])
            ->add('linkedin', UrlType::class, [
                'label' => 'Profil LinkedIn',
                'mapped' => false,
                'required' => false,
                'attr' => ['placeholder' => 'https://linkedin.com/in/...'],
            ])
            ->add('portfolio', UrlType::class, [
                'label' => 'Portfolio',
                'mapped' => false,
                'required' => false,
                'attr' => ['placeholder' => 'https://...'],
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
            ])
            
            // Champs spécifiques Apprenant
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
            ])
            ->add('niveauEtude', ChoiceType::class, [
                'label' => 'Niveau d\'étude',
                'mapped' => false,
                'required' => false,
                'choices' => [
                    'Primaire' => 'primaire',
                    'Secondaire' => 'secondaire',
                    'Universitaire' => 'universitaire',
                    'Master' => 'master',
                    'Doctorat' => 'doctorat',
                ],
                'placeholder' => 'Choisissez...',
            ])
            ->add('objectif', TextareaType::class, [
                'label' => 'Objectifs d\'apprentissage',
                'mapped' => false,
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => 'Décrivez vos objectifs...'],
            ])
            
            // Champs spécifiques Société
            ->add('nomSociete', TextType::class, [
                'label' => 'Nom de la société',
                'mapped' => false,
                'required' => false,
            ])
            ->add('secteur', TextType::class, [
                'label' => 'Secteur d\'activité',
                'mapped' => false,
                'required' => false,
            ])
            ->add('descriptionSociete', TextareaType::class, [
                'label' => 'Description de la société',
                'mapped' => false,
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('adresse', TextareaType::class, [
                'label' => 'Adresse',
                'mapped' => false,
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('siteWeb', UrlType::class, [
                'label' => 'Site web',
                'mapped' => false,
                'required' => false,
                'attr' => ['placeholder' => 'https://...'],
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
            ])
            
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'attr' => ['placeholder' => 'Laissez vide pour ne pas changer'],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => ['placeholder' => 'Confirmez le nouveau mot de passe'],
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
