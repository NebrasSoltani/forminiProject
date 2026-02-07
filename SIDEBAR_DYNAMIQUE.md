# Sidebar Dynamique - Documentation

## Modifications apportées

### 1. Service Twig Extension (`src/Twig/SidebarExtension.php`)

Un service Twig a été créé pour générer dynamiquement le menu du sidebar en fonction du rôle de l'utilisateur connecté.

**Rôles supportés:**
- **Admin** : Menu complet avec gestion des utilisateurs, formations, inscriptions, paiements et rapports
- **Formateur** : Menu pour gérer ses formations, ses apprenants et son profil
- **Apprenant** : Menu pour découvrir les formations, suivre ses cours, ses favoris, quiz et certificats

### 2. Template Sidebar (`templates/sidebar.html.twig`)

Le template sidebar a été mis à jour pour:
- Utiliser la fonction Twig `get_sidebar_menu()` pour charger le menu dynamiquement
- Afficher le nom de l'utilisateur connecté dans le header
- Simplifier le code en retirant les éléments statiques inutiles

### 3. Structure du menu

Le menu est généré dynamiquement avec:
- **Sections séparées** par des dividers
- **Icônes** Feather Icons pour chaque élément
- **Routes Symfony** dynamiques

## Utilisation

Le sidebar s'adapte automatiquement selon le rôle de l'utilisateur (`roleUtilisateur`) dans l'entité User:
- `admin` → Menu administrateur
- `formateur` → Menu formateur
- `apprenant` → Menu apprenant

## Routes à créer

Certaines routes référencées dans le menu n'existent peut-être pas encore et devront être créées:

### Admin
- `admin_users`
- `admin_formations`
- `admin_inscriptions`
- `admin_paiements`
- `admin_reports`

### Formateur
- `formateur_apprenants`
- `formateur_profile`

### Apprenant
- `apprenant_favoris`
- `apprenant_quiz_list`
- `apprenant_certificats`
- `apprenant_paiements`
- `apprenant_profile`

## Personnalisation

Pour ajouter ou modifier des éléments du menu, éditez le fichier:
`src/Twig/SidebarExtension.php`

Structure d'un élément de menu:
```php
[
    'label' => 'Nom affiché',
    'icon' => 'nom-icone-feather',
    'route' => 'nom_route_symfony',
]
```

Structure d'un divider:
```php
[
    'type' => 'divider',
    'label' => 'Titre de la section'
]
```

## Notes importantes

- Le service utilise `Symfony\Bundle\SecurityBundle\Security` pour récupérer l'utilisateur connecté
- Le cache Symfony a été vidé après les modifications
- Les icônes utilisent Feather Icons (déjà inclus dans le template)
