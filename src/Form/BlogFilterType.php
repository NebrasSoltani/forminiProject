<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Evenement;
use App\Repository\UserRepository;
use App\Repository\EvenementRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class BlogFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'label' => 'Rechercher',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Titre, contenu, auteur...'
                ]
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => $this->getCategoryChoices($options['categories']),
                'required' => false,
                'placeholder' => 'Toutes les catégories',
                'attr' => ['class' => 'form-control']
            ])
            ->add('isPublie', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Tous' => '',
                    'Publiés' => '1',
                    'Brouillons' => '0',
                ],
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('auteur', EntityType::class, [
                'class' => User::class,
                'query_builder' => function (UserRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.roleUtilisateur = :role')
                        ->setParameter('role', 'admin')
                        ->orderBy('u.nom', 'ASC');
                },
                'choice_label' => function (User $user) {
                    return $user->getPrenom() . ' ' . $user->getNom();
                },
                'label' => 'Auteur',
                'required' => false,
                'placeholder' => 'Tous les auteurs',
                'attr' => ['class' => 'form-control']
            ])
            ->add('evenement', EntityType::class, [
                'class' => Evenement::class,
                'query_builder' => function (EvenementRepository $er) {
                    return $er->createQueryBuilder('e')
                        ->orderBy('e.titre', 'ASC');
                },
                'choice_label' => 'titre',
                'label' => 'Événement associé',
                'required' => false,
                'placeholder' => 'Tous les événements',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateFrom', DateType::class, [
                'label' => 'Du',
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'class' => 'form-control datepicker',
                    'placeholder' => 'AAAA-MM-JJ'
                ]
            ])
            ->add('dateTo', DateType::class, [
                'label' => 'Au',
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'class' => 'form-control datepicker',
                    'placeholder' => 'AAAA-MM-JJ'
                ]
            ])
            ->add('filter', SubmitType::class, [
                'label' => 'Filtrer',
                'attr' => [
                    'class' => 'btn btn-primary',
                    'name' => 'filter'
                ]
            ])
            ->add('reset', SubmitType::class, [
                'label' => 'Réinitialiser',
                'attr' => [
                    'class' => 'btn btn-secondary',
                    'name' => 'reset'
                ]
            ]);
    }

    private function getCategoryChoices(array $categories): array
    {
        $choices = [];
        foreach ($categories as $category) {
            $choices[$category] = $category;
        }
        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'categories' => [],
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return ''; // Important pour avoir des URLs propres
    }
}