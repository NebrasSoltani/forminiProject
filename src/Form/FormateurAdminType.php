<?php

namespace App\Form;

use App\Entity\Formateur;
use App\Enum\Gouvernorat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
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
            ->add('profession', TextType::class, [
                'property_path' => 'user.profession',
                'required' => false,
            ])
            ->add('specialite', TextType::class, [
                'required' => false,
            ])
            ->add('bio', TextareaType::class, [
                'required' => false,
            ])
            ->add('experienceAnnees', IntegerType::class, [
                'required' => false,
            ])
            ->add('linkedin', UrlType::class, [
                'required' => false,
            ])
            ->add('portfolio', UrlType::class, [
                'required' => false,
            ])
            ->add('cv', TextType::class, [
                'required' => false,
            ])
            ->add('isVerifie', CheckboxType::class, [
                'required' => false,
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => !$options['is_edit'],
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
