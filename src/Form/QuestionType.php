<?php

namespace App\Form;

use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('enonce', TextareaType::class, [
                'label' => 'Énoncé de la question',
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'constraints' => [
                    new NotBlank(['message' => 'L\'énoncé est obligatoire']),
                    new Length([
                        'min' => 5,
                        'max' => 1000,
                        'minMessage' => 'L\'énoncé doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'L\'énoncé ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de question',
                'choices' => [
                    'QCM (Choix multiples)' => 'qcm',
                    'Vrai/Faux' => 'vrai_faux',
                    'Texte libre' => 'texte',
                ],
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Le type de question est obligatoire'])
                ]
            ])
            ->add('points', IntegerType::class, [
                'label' => 'Points',
                'attr' => ['class' => 'form-control', 'min' => 1],
                'constraints' => [
                    new NotBlank(['message' => 'Les points sont obligatoires']),
                    new Range([
                        'min' => 1,
                        'max' => 100,
                        'notInRangeMessage' => 'Les points doivent être entre {{ min }} et {{ max }}'
                    ])
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
            ->add('explication', TextareaType::class, [
                'label' => 'Explication (optionnel)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Explication affichée après la réponse'],
                'constraints' => [
                    new Length(['max' => 500])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
        ]);
    }
}