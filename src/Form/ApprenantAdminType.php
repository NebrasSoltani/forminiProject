<?php

namespace App\Form;

use App\Entity\Apprenant;
use App\Enum\Gouvernorat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApprenantAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'property_path' => 'user.email',
            ])
            ->add('nom', TextType::class, [
                'property_path' => 'user.nom',
            ])
            ->add('prenom', TextType::class, [
                'property_path' => 'user.prenom',
            ])
            ->add('telephone', TelType::class, [
                'property_path' => 'user.telephone',
                'required' => true,
            ])
            ->add('gouvernorat', ChoiceType::class, [
                'property_path' => 'user.gouvernorat',
                'required' => true,
                'choices' => array_combine(
                    array_map(fn(Gouvernorat $g) => $g->value, Gouvernorat::cases()),
                    Gouvernorat::cases()
                ),
                'placeholder' => 'Choisissez un gouvernorat',
            ])
            ->add('dateNaissance', DateType::class, [
                'property_path' => 'user.dateNaissance',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('niveauEtude', TextType::class, [
                'property_path' => 'user.niveauEtude',
                'required' => false,
            ])
            ->add('genre', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Homme' => 'homme',
                    'Femme' => 'femme',
                    'Autre' => 'autre',
                ],
                'placeholder' => 'Choisissez...',
            ])
            ->add('etatCivil', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Célibataire' => 'celibataire',
                    'Marié(e)' => 'marie',
                    'Divorcé(e)' => 'divorce',
                    'Veuf(ve)' => 'veuf',
                ],
                'placeholder' => 'Choisissez...',
            ])
            ->add('objectif', TextareaType::class, [
                'required' => false,
            ])
            ->add('domainesInteret', ChoiceType::class, [
                'label' => "Centres d'intérêt",
                'choices' => [
                    'Informatique' => 'informatique',
                    'Marketing' => 'marketing',
                    'Design' => 'design',
                    'Finance' => 'finance',
                    'Communication' => 'communication',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => !$options['is_edit'],
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                        'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Apprenant::class,
            'is_edit' => false,
        ]);
    }
}
