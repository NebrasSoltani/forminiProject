<?php

namespace App\Form;

use App\Entity\Quiz;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;

class QuizType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du quiz',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire']),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'constraints' => [
                    new Length(['max' => 1000])
                ]
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (en minutes)',
                'attr' => ['class' => 'form-control', 'min' => 1],
                'constraints' => [
                    new NotBlank(['message' => 'La durée est obligatoire']),
                    new Range([
                        'min' => 1,
                        'max' => 300,
                        'notInRangeMessage' => 'La durée doit être entre {{ min }} et {{ max }} minutes'
                    ])
                ]
            ])
            ->add('noteMinimale', IntegerType::class, [
                'label' => 'Note minimale pour réussir (%)',
                'attr' => ['class' => 'form-control', 'min' => 0, 'max' => 100],
                'constraints' => [
                    new NotBlank(['message' => 'La note minimale est obligatoire']),
                    new Range([
                        'min' => 0,
                        'max' => 100,
                        'notInRangeMessage' => 'La note minimale doit être entre {{ min }} et {{ max }}'
                    ])
                ]
            ])
            ->add('afficherCorrection', CheckboxType::class, [
                'label' => 'Afficher la correction après le quiz',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('melanger', CheckboxType::class, [
                'label' => 'Mélanger les questions',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quiz::class,
        ]);
    }
}