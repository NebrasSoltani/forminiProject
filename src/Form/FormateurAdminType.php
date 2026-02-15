<?php

namespace App\Form;

use App\Entity\Formateur;
use App\Enum\Gouvernorat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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


class FormateurAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'property_path' => 'user.nom',
                'label' => 'Nom',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: Dupont'
                ]
            ])
            ->add('prenom', TextType::class, [
                'property_path' => 'user.prenom',
                'label' => 'Prénom',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: Marie'
                ]
            ])
            ->add('email', EmailType::class, [
                'property_path' => 'user.email',
                'label' => 'Email',
                'required' => true,
                'attr' => [
                    'placeholder' => 'votre@email.com',
                    'autocomplete' => 'email'
                ],
                
            ])
            ->add('telephone', TelType::class, [
                'property_path' => 'user.telephone',
                'label' => 'Téléphone',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: +216 20 123 456'
                ]
            ])
            ->add('gouvernorat', ChoiceType::class, [
                'property_path' => 'user.gouvernorat',
                'label' => 'Gouvernorat',
                'required' => true,
                'choices' => array_combine(
                    array_map(fn(Gouvernorat $g) => $g->name, Gouvernorat::cases()),
                    Gouvernorat::cases()
                ),
                'placeholder' => 'Choisissez un gouvernorat',
            ])
            ->add('dateNaissance', DateType::class, [
                'property_path' => 'user.dateNaissance',
                'label' => 'Date de naissance',
                'required' => true,
                'widget' => 'single_text',
                'attr' => [
                    'placeholder' => 'JJ/MM/AAAA'
                ]
            ])
            ->add('profession', TextType::class, [
                'property_path' => 'user.profession',
                'label' => 'Profession',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: Ingénieur, Étudiant'
                ]
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/jpeg,image/png,image/jpg,image/gif'
                ],
                
            ])
            ->add('specialite', TextType::class, [
                'label' => 'Spécialité',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: Développement Web, Data Science'
                ]
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Décrivez brièvement votre parcours et expertise...'
                ]
            ])
            ->add('experienceAnnees', IntegerType::class, [
                'label' => 'Années d\'expérience',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Années d\'expérience'
                ]
            ])
            ->add('linkedin', UrlType::class, [
                'label' => 'Profil LinkedIn',
                'required' => false,
                'attr' => [
                    'placeholder' => 'URL de votre profil LinkedIn'
                ]
            ])
            ->add('portfolio', UrlType::class, [
                'label' => 'Portfolio',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Lien vers votre portfolio'
                ]
            ])
            ->add('cv', FileType::class, [
                'label' => 'CV / Portfolio',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'application/pdf,.doc,.docx'
                ],
               
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => !$options['is_edit'],
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'placeholder' => '••••••••',
                        'autocomplete' => 'new-password'
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => [
                        'placeholder' => '••••••••',
                        'autocomplete' => 'new-password'
                    ]
                ],
                'invalid_message' => 'Les mots de passe doivent être identiques',
                
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formateur::class,
            'is_edit' => false,
        ]);
    }
}
