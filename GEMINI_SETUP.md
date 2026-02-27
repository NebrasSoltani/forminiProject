# 📋 Guide d'intégration Gemini API

## ⚠️ Erreur 404: La clé API actuelle ne fonctionne pas

Les tests montrent que les appels à Gemini retournent HTTP 404. Cela peut être dû à:

1. **Clé API invalide ou expirée**
2. **API Generative Language non activée** dans Google Cloud Console
3. **Mauvaise organisation ou projet** sélectionné

## ✅ Solution: Obtenir une clé API valide

### Étape 1: Accéder à Google AI Studio (Gratuit)

1. Allez sur https://makersuite.google.com/app/apikey
2. Cliquez sur **"Get API Key"**
3. Sélectionnez **"Create new free API key in new Google Cloud project"**
4. Acceptez les conditions et générez la clé

### Étape 2: Configurer votre clé

1. Copiez votre clé API (elle commence par `AIza...`)
2. Ouvrez le fichier `.env` dans le projet
3. Remplacez `GEMINI_API_KEY=your_key_here` par votre vraie clé
4. **Important**: Ne commitez JAMAIS cette clé sur GitHub (elle est déjà dans `.gitignore`)

### Étape 3: Tester

```bash
cd c:\Users\msi\Desktop\formini\forminiProject
php test_api.php
```

Si vous voyez ✅ "SUCCÈS" avec une réponse de Gemini, c'est prêt!

## 📝 Fonctionnement actuel

Pour l'instant, le service `ChatbotAnalyseService` est configuré pour:

1. **Essayer d'appeler Gemini** pour générer une explication
2. **Si Gemini échoue**, utiliser les explications stockées en base de données
3. **Si aucune explication en base**, afficher un message par défaut

Donc même **sans clé API valide**, l'application fonctionnera correctement avec les explications stockées!

## 🔧 Endpoints disponibles

- `gemini-pro` (gratuit, rapide)
- `gemini-1.5-pro` (plus puissant)
- `gemini-1.5-flash` (modèle rapide)

Le service essaye automatiquement `gemini-pro` en premier.

## 💡 Utilisation dans le code

```php
// Le service utilise Gemini automatiquement s'il y a une clé API valide
$resultat = $chatbotService->analyserResultat($resultatQuiz);
// Les explications seront générées par Gemini si possible,
// sinon fallback sur la base de données
```

## 🚀 Limites gratuites

- 60 appels par minute (limite libre)
- 1500 appels par jour
- Idéal pour une plateforme d'apprentissage

## 🆘 Support

Si le problème persiste:

1. Vérifiez que votre compte Google n'a pas d'avertissements de sécurité
2. Essayez de générer une nouvelle clé
3. Vérifiez que Javascript/API est activée dans votre compte Google
