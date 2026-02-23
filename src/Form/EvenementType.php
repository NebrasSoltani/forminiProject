<?php

namespace App\Form;

use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l\'événement',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez le titre']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 6, 'placeholder' => 'Description complète de l\'événement']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'événement',
                'choices' => [
                    'Conférence' => 'Conférence',
                    'Atelier' => 'Atelier',
                    'Webinaire' => 'Webinaire',
                    'Formation' => 'Formation',
                    'Networking' => 'Networking',
                    'Séminaire' => 'Séminaire',
                    'Hackathon' => 'Hackathon',
                    'Autre' => 'Autre',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateDebut', DateTimeType::class, [
                'label' => 'Date et heure de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateFin', DateTimeType::class, [
                'label' => 'Date et heure de fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Adresse ou lien de l\'événement']
            ])
            ->add('nombrePlaces', IntegerType::class, [
                'label' => 'Nombre de places',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nombre de participants maximum']
            ])
            ->add('image', FileType::class, [
                'label' => 'Image de l\'événement',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, GIF)',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('isActif', CheckboxType::class, [
                'label' => 'Activer l\'événement',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('filieres', ChoiceType::class, [
                'label' => 'Filières cibles',
                'multiple' => true,
                'expanded' => false,
                'choices' => [
                    'Informatique' => 'informatique',
                    'Intelligence Artificielle' => 'ia',
                    'Data Science' => 'data',
                    'Développement Web' => 'web',
                    'Cybersécurité' => 'cybersecurite',
                    'Marketing Digital' => 'marketing',
                    'Business' => 'business',
                    'Design' => 'design',
                ],
                'attr' => [
                    'class' => 'form-control select2',
                    'data-placeholder' => 'Sélectionnez des filières'
                ],
                'required' => false,
            ])
            ->add('tags', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Tags (séparés par des virgules)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: python, startup, innovation'
                ],
                'help' => 'Séparez les tags par des virgules'
            ])
            ->add('image360', FileType::class, [
                'label' => 'Image 360° (Equirectangular)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image 360 valide (JPG, PNG, WEBP)',
                    ])
                ],
                'help' => 'Téléchargez une image au format equirectangular pour une immersion 360°'
            ])
            ->add('urlStreetView', TextType::class, [
                'label' => 'URL Google Street View (Embed)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://www.google.com/maps/embed?pb=...'
                ],
                'help' => 'Collez ici le lien "Intégrer une carte" de Google Street View'
            ])
            ->add('live', CheckboxType::class, [
                'label' => 'Événement en direct (En ligne)',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('streamUrl', TextType::class, [
                'label' => 'URL du flux vidéo (HLS/.m3u8)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://.../index.m3u8'
                ],
                'help' => 'Collez ici l\'URL du flux HLS (fichier .m3u8)'
            ]);

        $builder->get('tags')
            ->addModelTransformer(new \Symfony\Component\Form\CallbackTransformer(
                function ($tagsAsArray): string {
                    // transform the array to a string
                    return implode(', ', $tagsAsArray ?? []);
                },
                function ($tagsAsString): array {
                    // transform the string back to an array
                    if (!$tagsAsString) {
                        return [];
                    }
                    return array_map('trim', explode(',', $tagsAsString));
                }
            ));
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}
