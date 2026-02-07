<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CandidatureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lettreMotivation', TextareaType::class, [
                'label' => 'Lettre de motivation',
                'required' => true,
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'Expliquez pourquoi vous êtes le candidat idéal pour ce stage...'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La lettre de motivation est obligatoire'
                    ]),
                    new Length([
                        'min' => 100,
                        'max' => 5000,
                        'minMessage' => 'La lettre de motivation doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La lettre de motivation ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('cv', FileType::class, [
                'label' => 'Curriculum Vitae (PDF)',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez télécharger votre CV'
                    ]),
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier PDF valide',
                        'maxSizeMessage' => 'Le fichier ne doit pas dépasser {{ limit }} {{ suffix }}'
                    ])
                ],
            ])
        ;
    }
}
