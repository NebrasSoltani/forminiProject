<?php

namespace App\Form;

use App\Entity\OffreStage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class OffreStageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du stage',
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire']),
                    new Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('entreprise', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom de l\'entreprise est obligatoire']),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du poste',
                'attr' => ['rows' => 8],
                'constraints' => [
                    new NotBlank(['message' => 'La description est obligatoire']),
                    new Length([
                        'min' => 50,
                        'max' => 5000,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('domaine', TextType::class, [
                'label' => 'Domaine',
                'required' => false,
                'help' => 'Ex: Informatique, Marketing, Finance',
                'constraints' => [
                    new Length(['max' => 50])
                ]
            ])
            ->add('typeStage', ChoiceType::class, [
                'label' => 'Type de stage',
                'choices' => [
                    'Stage d\'observation' => 'stage_observation',
                    'Stage d\'application' => 'stage_application',
                    'Stage de perfectionnement' => 'stage_perfectionnement',
                    'Projet de fin d\'études (PFE)' => 'pfe',
                ],
                'placeholder' => 'Choisir le type de stage',
                'constraints' => [
                    new NotBlank(['message' => 'Le type de stage est obligatoire'])
                ]
            ])
            ->add('duree', TextType::class, [
                'label' => 'Durée',
                'help' => 'Ex: 3 mois, 6 mois',
                'constraints' => [
                    new NotBlank(['message' => 'La durée est obligatoire']),
                    new Length(['max' => 100])
                ]
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'required' => false,
                'constraints' => [
                    new GreaterThan([
                        'value' => 'today',
                        'message' => 'La date de début doit être dans le futur'
                    ])
                ]
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'help' => 'Ville ou adresse',
                'constraints' => [
                    new NotBlank(['message' => 'Le lieu est obligatoire']),
                    new Length(['max' => 255])
                ]
            ])
            ->add('competencesRequises', TextareaType::class, [
                'label' => 'Compétences requises',
                'attr' => ['rows' => 5],
                'required' => false,
                'constraints' => [
                    new Length(['max' => 2000])
                ]
            ])
            ->add('profilDemande', TextType::class, [
                'label' => 'Profil demandé',
                'required' => false,
                'help' => 'Ex: Étudiant en Master 2 Informatique',
                'constraints' => [
                    new Length(['max' => 100])
                ]
            ])
            ->add('remuneration', TextType::class, [
                'label' => 'Rémunération',
                'required' => false,
                'help' => 'Ex: Gratifié, 500€/mois',
                'constraints' => [
                    new Length(['max' => 100])
                ]
            ])
            ->add('contactEmail', EmailType::class, [
                'label' => 'Email de contact',
                'required' => false,
                'help' => 'Email pour recevoir les candidatures',
                'constraints' => [
                    new Email(['message' => 'Veuillez saisir un email valide'])
                ]
            ])
            ->add('contactTel', TelType::class, [
                'label' => 'Téléphone de contact',
                'required' => false,
                'constraints' => [
                    new Regex([
                        'pattern' => '/^\+?[0-9\s\-\(\)]{8,20}$/',
                        'message' => 'Numéro de téléphone invalide (8-20 chiffres)'
                    ])
                ]
            ]);
        
        // Le champ statut n'est affiché que lors de l'édition
        if ($options['is_edit']) {
            $builder->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Publiée' => 'publiee',
                    'Fermée' => 'fermee',
                    'Expirée' => 'expiree',
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OffreStage::class,
            'is_edit' => false,
        ]);
    }
}
