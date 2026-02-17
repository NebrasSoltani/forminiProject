<?php

namespace App\Form;

use App\Entity\Societe;
use App\Enum\Gouvernorat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SocieteAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'property_path' => 'user.nom',
                'label' => 'Nom',
                'required' => true,
            ])
            ->add('prenom', TextType::class, [
                'property_path' => 'user.prenom',
                'label' => 'Prénom',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'property_path' => 'user.email',
                'label' => 'Email',
                'required' => true,
            ])
            ->add('telephone', TelType::class, [
                'property_path' => 'user.telephone',
                'label' => 'Téléphone',
                'required' => true,
            ])
            ->add('gouvernorat', ChoiceType::class, [
                'property_path' => 'user.gouvernorat',
                'label' => 'Gouvernorat',
                'required' => true,
                'choices' => array_combine(
                    array_map(fn(Gouvernorat $g) => $g->value, Gouvernorat::cases()),
                    Gouvernorat::cases()
                ),
                'placeholder' => 'Choisissez un gouvernorat',
            ])
            ->add('dateNaissance', DateType::class, [
                'property_path' => 'user.dateNaissance',
                'label' => 'Date de naissance',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/jpg,image/gif'
                ]
            ])
            ->add('nomSociete', TextType::class, [
                'label' => 'Nom de la société',
                'required' => true,
            ])
            ->add('secteur', TextType::class, [
                'label' => 'Secteur d\'activité',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('adresse', TextareaType::class, [
                'label' => 'Adresse',
                'required' => false,
            ])
            ->add('siteWeb', UrlType::class, [
                'label' => 'Site web',
                'required' => false,
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
            'data_class' => Societe::class,
            'is_edit' => false,
        ]);
    }
}
