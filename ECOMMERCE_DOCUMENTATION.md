# Système E-commerce - Documentation

## Vue d'ensemble
Système de boutique en ligne pour la vente de produits pédagogiques (livres, cahiers, stylos, matériel, etc.)

## Fonctionnalités

### Pour l'Administrateur
- **Gestion des produits** : CRUD complet (Créer, Lire, Modifier, Supprimer)
- **Upload d'images** : Téléchargement d'images pour chaque produit
- **Gestion du stock** : Suivi automatique du stock disponible
- **Catégorisation** : Organisation des produits par catégorie

### Pour les Formateurs et Apprenants
- **Catalogue de produits** : Navigation avec filtres par catégorie
- **Panier d'achat** : Ajout/retrait de produits, modification des quantités
- **Passage de commande** : Validation et enregistrement des commandes
- **Historique des commandes** : Consultation des commandes passées avec détails

## Architecture

### Entités
1. **Produit** (`src/Entity/Produit.php`)
   - Nom, catégorie, description
   - Prix, stock, image
   - Statut (actif/inactif)
   - Date de création

2. **Commande** (`src/Entity/Commande.php`)
   - Référence unique (CMD-xxxxx)
   - Utilisateur
   - Date de commande
   - Statut (en_attente, confirmee, en_cours, livree, annulee)
   - Total, adresse de livraison, téléphone
   - Collection d'items (CommandeItem)

3. **CommandeItem** (`src/Entity/CommandeItem.php`)
   - Produit, nom du produit
   - Quantité, prix unitaire
   - Calcul automatique du sous-total

### Controllers

#### ProduitController (`src/Controller/ProduitController.php`)
**Accès** : Administrateur uniquement (ROLE_ADMIN)

**Routes** :
- `GET /admin/produit/` - Liste des produits
- `GET/POST /admin/produit/new` - Créer un produit
- `GET /admin/produit/{id}` - Voir un produit
- `GET/POST /admin/produit/{id}/edit` - Modifier un produit
- `POST /admin/produit/{id}/delete` - Supprimer un produit

#### BoutiqueController (`src/Controller/BoutiqueController.php`)
**Accès** : Tous les utilisateurs connectés (ROLE_USER)

**Routes** :
- `GET /boutique` - Catalogue de produits (avec filtre par catégorie)
- `GET /boutique/produit/{id}` - Détails d'un produit
- `POST /boutique/panier/ajouter/{id}` - Ajouter au panier
- `GET /boutique/panier` - Voir le panier
- `GET /boutique/panier/retirer/{id}` - Retirer du panier
- `POST /boutique/commander` - Passer une commande
- `GET /boutique/mes-commandes` - Historique des commandes
- `GET /boutique/commande/{id}` - Détails d'une commande

### Formulaires
**ProduitType** (`src/Form/ProduitType.php`)
- Formulaire complet pour la création/modification de produits
- Validation des champs (obligatoire, positif, format de fichier)
- Upload d'image avec contraintes (2MB max, formats JPG/PNG/GIF/WEBP)

### Templates

#### Admin
- `templates/produit/index.html.twig` - Liste des produits
- `templates/produit/form.html.twig` - Formulaire de création/modification
- `templates/produit/show.html.twig` - Détails d'un produit

#### Boutique
- `templates/boutique/index.html.twig` - Catalogue avec filtres
- `templates/boutique/show.html.twig` - Page produit avec ajout au panier
- `templates/boutique/panier.html.twig` - Panier d'achat
- `templates/boutique/mes_commandes.html.twig` - Liste des commandes
- `templates/boutique/commande_detail.html.twig` - Détails d'une commande

## Fonctionnement du Panier

### Stockage en Session
Le panier est stocké dans la session PHP :
```php
$panier = [
    'product_id' => [
        'quantite' => 2,
        'produit' => Produit // Objet Produit complet
    ]
]
```

### Gestion du Stock
- Vérification du stock disponible avant ajout au panier
- Déduction automatique du stock lors de la validation de commande
- Affichage visuel du niveau de stock (En stock, Stock limité, Rupture)

### Validation de Commande
1. L'utilisateur ajoute des produits au panier
2. Modification des quantités possible depuis le panier
3. Validation avec génération d'une référence unique
4. Création des CommandeItem liés
5. Déduction automatique du stock
6. Vidage du panier

## Menu Sidebar
Le système a été intégré au menu dynamique (`src/Twig/SidebarExtension.php`) :

### Menu Admin
- Produits → `/admin/produit`
- Ajouter Produit → `/admin/produit/new`

### Menu Formateur
- Produits Pédagogiques → `/boutique`
- Mes Commandes → `/boutique/mes-commandes`

### Menu Apprenant
- Produits Pédagogiques → `/boutique`
- Mes Commandes → `/boutique/mes-commandes`

## Upload d'Images
**Répertoire** : `public/uploads/produits/`
- Création automatique du répertoire
- Slug du nom de fichier + timestamp unique
- Formats acceptés : JPG, PNG, GIF, WEBP
- Taille maximale : 2MB

## Base de Données

### Tables Créées
1. **produit**
   - Colonnes : id, nom, categorie, description, prix, stock, image, statut, date_creation

2. **commande**
   - Colonnes : id, reference, date_commande, statut, total, adresse_livraison, telephone, utilisateur_id
   - Foreign Key : utilisateur_id → user(id) ON DELETE CASCADE

3. **commande_item**
   - Colonnes : id, quantite, prix_unitaire, nom_produit, commande_id, produit_id
   - Foreign Keys : 
     - commande_id → commande(id) ON DELETE CASCADE
     - produit_id → produit(id)

### Migrations
Les tables ont été créées avec `doctrine:schema:update --force`

## Statuts de Commande
1. **en_attente** - Commande créée, en attente de traitement
2. **confirmee** - Commande confirmée par l'admin
3. **en_cours** - Commande en cours de préparation
4. **livree** - Commande livrée
5. **annulee** - Commande annulée

## Catégories de Produits
- **livre** - Livres pédagogiques
- **cahier** - Cahiers et carnets
- **stylo** - Stylos et instruments d'écriture
- **materiel** - Matériel pédagogique divers
- **autre** - Autres produits

## Sécurité
- Protection CSRF sur tous les formulaires
- Vérification du stock avant commande
- Validation des données avec contraintes Symfony
- Restriction d'accès par rôles (ROLE_ADMIN, ROLE_USER)

## Messages Flash
Le système utilise des messages flash pour informer l'utilisateur :
- **success** - Opération réussie (création, modification, commande)
- **error** - Erreur (upload, stock insuffisant, etc.)
- **warning** - Avertissement
- **info** - Information

## Prochaines Améliorations Possibles
1. Système de paiement en ligne
2. Gestion des promotions et codes promo
3. Système de notation et commentaires
4. Export des commandes en PDF
5. Statistiques de vente pour l'admin
6. Notifications email après commande
7. Suivi de livraison
8. Wishlist (liste de souhaits)
