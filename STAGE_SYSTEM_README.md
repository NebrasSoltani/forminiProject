# Système d'Offres de Stage

## Présentation
Ce système permet aux sociétés de publier des offres de stage et aux apprenants de postuler avec CV et lettre de motivation.

## Rôles et Fonctionnalités

### Rôle Société (`roleUtilisateur = 'societe'`)
Les sociétés peuvent:
- Publier des offres de stage
- Modifier leurs offres
- Consulter les candidatures reçues
- Accepter ou refuser les candidatures

**Menu Société:**
- Mes Offres
- Publier une Offre

**Routes:**
- `/societe/offres` - Liste des offres
- `/societe/offres/new` - Créer une offre
- `/societe/offres/{id}` - Voir une offre et ses candidatures
- `/societe/offres/{id}/edit` - Modifier une offre

### Rôle Apprenant
Les apprenants peuvent:
- Parcourir les offres de stage publiées
- Filtrer par type de stage
- Postuler avec lettre de motivation et CV (PDF)
- Consulter leurs candidatures et leur statut

**Menu Apprenant:**
- Offres de Stage
- Mes Candidatures

**Routes:**
- `/stages` - Parcourir les offres (avec filtre par type)
- `/stages/{id}` - Voir détails d'une offre
- `/stages/{id}/postuler` - Postuler à une offre
- `/stages/mes-candidatures` - Mes candidatures

## Types de Stage
- Stage d'observation
- Stage d'application
- Stage de perfectionnement
- PFE (Projet de Fin d'Études)

## Workflow des Candidatures

1. **Apprenant postule** → Statut: "en_attente"
2. **Société consulte** → Peut accepter ou refuser
3. **Statuts possibles:**
   - `en_attente` (jaune) - En attente de réponse
   - `acceptee` (vert) - Candidature acceptée
   - `refusee` (rouge) - Candidature refusée

## Statuts des Offres
- `publiee` - Offre active visible par tous
- `fermee` - Offre fermée (plus de candidatures)
- `expiree` - Offre expirée

## Fichiers Créés

### Entités
- `src/Entity/OffreStage.php` - Offre de stage
- `src/Entity/Candidature.php` - Candidature apprenant

### Repositories
- `src/Repository/OffreStageRepository.php`
- `src/Repository/CandidatureRepository.php`

### Controllers
- `src/Controller/SocieteController.php` - Gestion offres société
- `src/Controller/ApprenantStageController.php` - Navigation et candidatures

### Forms
- `src/Form/OffreStageType.php` - Formulaire offre
- `src/Form/CandidatureType.php` - Formulaire candidature

### Templates Société
- `templates/societe/offres/index.html.twig` - Liste des offres
- `templates/societe/offres/form.html.twig` - Créer/modifier offre
- `templates/societe/offres/show.html.twig` - Détails + candidatures

### Templates Apprenant
- `templates/apprenant/stages/index.html.twig` - Parcourir offres
- `templates/apprenant/stages/show.html.twig` - Détails offre
- `templates/apprenant/stages/postuler.html.twig` - Formulaire candidature
- `templates/apprenant/stages/mes_candidatures.html.twig` - Mes candidatures

## Base de Données
Tables créées:
- `offre_stage` - Offres de stage
- `candidature` - Candidatures

## Uploads
Répertoire CV: `public/uploads/cv/`
Format accepté: PDF uniquement, max 5MB

## Pour Tester

### 1. Créer un utilisateur société
```sql
UPDATE user SET role_utilisateur = 'societe' WHERE email = 'societe@example.com';
```

### 2. Se connecter en tant que société
- Publier une offre de stage
- Définir le type, durée, dates, compétences

### 3. Se connecter en tant qu'apprenant
- Parcourir les offres
- Cliquer sur "Voir détails"
- Postuler avec lettre de motivation et CV
- Consulter "Mes Candidatures"

### 4. Retourner en société
- Voir les candidatures reçues
- Accepter ou refuser les candidatures
- Optionnel: Ajouter un commentaire

## Sécurité
- Vérification du rôle sur toutes les routes
- CSRF protection sur les formulaires
- Validation des uploads (type PDF, taille max)
- Prevention des candidatures en double
- CASCADE delete (si offre supprimée → candidatures supprimées)

## Fonctionnalités Avancées Implémentées
- Détection automatique des candidatures déjà envoyées
- Filtrage des offres par type
- Modals Bootstrap pour afficher les détails des candidatures
- Badges de statut colorés
- Interface moderne avec icônes Font Awesome
- Responsive design
