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
                'attr' => [
                    'class' => 'form-control', 
                    'rows' => 3,
                    'minlength' => 5,
                    'maxlength' => 1000
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de question',
                'choices' => [
                    'QCM (Choix multiples)' => 'qcm',
                    'Vrai/Faux' => 'vrai_faux',
                    'Texte libre' => 'texte',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('points', IntegerType::class, [
                'label' => 'Points',
                'attr' => [
                    'class' => 'form-control', 
                    'min' => 1,
                    'max' => 100
                ]
            ])
            ->add('ordre', IntegerType::class, [
                'label' => 'Ordre',
                'attr' => [
                    'class' => 'form-control', 
                    'min' => 1,
                    'max' => 1000
                ]
            ])
            ->add('explication', TextareaType::class, [
                'label' => 'Explication courte (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => 'Explication rapide affichée après la réponse'
                ],
                'help' => 'Explication concise visible par l\'apprenant'
            ])
            // ⭐ NOUVEAU CHAMP POUR LE CHATBOT
            ->add('explicationsDetaillees', TextareaType::class, [
                'label' => 'Explications détaillées pour le chatbot (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Explications approfondies pour aider l\'apprenant à comprendre ses erreurs...'
                ],
                'help' => '💡 Ces explications seront utilisées par l\'assistant intelligent pour fournir une aide personnalisée à l\'apprenant en cas d\'erreur.'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
        ]);
    }
}
