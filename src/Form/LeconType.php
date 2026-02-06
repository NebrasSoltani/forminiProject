<?php

namespace App\Form;

use App\Entity\Lecon;
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
use Symfony\Component\Validator\Constraints\Url;

class LeconType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la leçon',
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
                    new Length(['max' => 500])
                ]
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu de la leçon',
                'required' => false,
                'attr' => [
                    'class' => 'form-control', 
                    'rows' => 15,
                    'placeholder' => 'Saisissez le contenu de votre leçon (HTML supporté)'
                ],
                'constraints' => [
                    new Length(['max' => 50000])
                ]
            ])
            ->add('ordre', IntegerType::class, [
                'label' => 'Ordre',
                'attr' => ['class' => 'form-control', 'min' => 1],
                'constraints' => [
                    new NotBlank(['message' => 'L\'ordre est obligatoire']),
                    new Range([
                        'min' => 1,
                        'max' => 1000,
                        'notInRangeMessage' => 'L\'ordre doit être entre {{ min }} et {{ max }}'
                    ])
                ]
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (en minutes)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'min' => 1],
                'constraints' => [
                    new Range([
                        'min' => 1,
                        'max' => 600,
                        'notInRangeMessage' => 'La durée doit être entre {{ min }} et {{ max }} minutes'
                    ])
                ]
            ])
            ->add('videoUrl', TextType::class, [
                'label' => 'URL de la vidéo YouTube',
                'required' => false,
                'attr' => [
                    'class' => 'form-control', 
                    'placeholder' => 'https://www.youtube.com/watch?v=VIDEO_ID ou https://youtu.be/VIDEO_ID'
                ],
                'help' => '💡 Vous pouvez coller n\'importe quelle URL YouTube, elle sera automatiquement convertie.',
                'constraints' => [
                    new Url(['message' => 'Veuillez saisir une URL valide'])
                ]
            ])
            ->add('gratuit', CheckboxType::class, [
                'label' => 'Leçon gratuite (accessible sans inscription)',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lecon::class,
        ]);
    }
}