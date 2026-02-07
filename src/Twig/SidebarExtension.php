<?php

namespace App\Twig;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SidebarExtension extends AbstractExtension
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_sidebar_menu', [$this, 'getSidebarMenu']),
        ];
    }

    public function getSidebarMenu(): array
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return [];
        }

        $roleUtilisateur = $user->getRoleUtilisateur();

        // Menu Admin
        if ($roleUtilisateur === 'admin' || in_array('ROLE_ADMIN', $user->getRoles())) {
            return [
                [
                    'label' => 'Dashboard',
                    'icon' => 'home',
                    'route' => 'admin_dashboard',
                ],
                [
                    'type' => 'divider',
                    'label' => 'Boutique'
                ],
                [
                    'label' => 'Produits',
                    'icon' => 'shopping-bag',
                    'route' => 'produit_index',
                ],
                [
                    'label' => 'Ajouter Produit',
                    'icon' => 'plus-circle',
                    'route' => 'produit_new',
                ],
                [
                    'label' => 'Toutes les Commandes',
                    'icon' => 'list',
                    'route' => 'admin_commandes',
                ],
            ];
        }

        // Menu Formateur
        if ($roleUtilisateur === 'formateur') {
            return [
                [
                    'label' => 'Dashboard',
                    'icon' => 'home',
                    'route' => 'formateur_dashboard',
                ],
                [
                    'type' => 'divider',
                    'label' => 'Mes Formations'
                ],
                [
                    'label' => 'Mes Formations',
                    'icon' => 'book',
                    'route' => 'formation_index',
                ],
                [
                    'label' => 'Créer Formation',
                    'icon' => 'plus-circle',
                    'route' => 'formation_new',
                ],
                [
                    'type' => 'divider',
                    'label' => 'Boutique'
                ],
                [
                    'label' => 'Produits Pédagogiques',
                    'icon' => 'shopping-cart',
                    'route' => 'boutique_index',
                ],
                [
                    'label' => 'Mes Commandes',
                    'icon' => 'package',
                    'route' => 'boutique_mes_commandes',
                ],
            ];
        }

        // Menu Apprenant
        if ($roleUtilisateur === 'apprenant') {
            return [
                [
                    'label' => 'Dashboard',
                    'icon' => 'home',
                    'route' => 'apprenant_dashboard',
                ],
                [
                    'type' => 'divider',
                    'label' => 'Formations'
                ],
                [
                    'label' => 'Découvrir',
                    'icon' => 'search',
                    'route' => 'apprenant_formations_index',
                ],
                [
                    'label' => 'Mes Formations',
                    'icon' => 'book',
                    'route' => 'apprenant_mes_formations',
                ],
                [
                    'label' => 'Mes Favoris',
                    'icon' => 'heart',
                    'route' => 'apprenant_mes_favoris',
                ],
                [
                    'type' => 'divider',
                    'label' => 'Stages'
                ],
                [
                    'label' => 'Offres de Stage',
                    'icon' => 'briefcase',
                    'route' => 'apprenant_stages_index',
                ],
                [
                    'label' => 'Mes Candidatures',
                    'icon' => 'file-text',
                    'route' => 'apprenant_mes_candidatures',
                ],
                [
                    'type' => 'divider',
                    'label' => 'Boutique'
                ],
                [
                    'label' => 'Produits Pédagogiques',
                    'icon' => 'shopping-cart',
                    'route' => 'boutique_index',
                ],
                [
                    'label' => 'Mes Commandes',
                    'icon' => 'package',
                    'route' => 'boutique_mes_commandes',
                ],
            ];
        }

        // Menu Société
        if ($roleUtilisateur === 'societe') {
            return [
                [
                    'label' => 'Dashboard',
                    'icon' => 'home',
                    'route' => 'societe_dashboard',
                ],
                [
                    'type' => 'divider',
                    'label' => 'Gestion des Offres'
                ],
                [
                    'label' => 'Mes Offres',
                    'icon' => 'briefcase',
                    'route' => 'societe_offres_index',
                ],
                [
                    'label' => 'Publier une Offre',
                    'icon' => 'plus-circle',
                    'route' => 'societe_offre_new',
                ],
            ];
        }

        return [];
    }
}
