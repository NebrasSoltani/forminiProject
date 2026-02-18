<?php

namespace App\Service;

use App\Entity\ResultatQuiz;
use App\Repository\QuestionRepository;
use App\Repository\ReponseRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class ChatbotAnalyseService
{
    private QuestionRepository $questionRepository;
    private ReponseRepository $reponseRepository;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private SendGridEmailSender $emailSender;
    private string $geminiApiKey;

    public function __construct(
        QuestionRepository $questionRepository,
        ReponseRepository $reponseRepository,
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        SendGridEmailSender $emailSender,
        string $geminiApiKey = ''
    ) {
        $this->questionRepository = $questionRepository;
        $this->reponseRepository = $reponseRepository;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->emailSender = $emailSender;
        $this->geminiApiKey = $geminiApiKey;
    }

    // ... (rest of the file until envoyerEmailFelicitation)

    private function envoyerEmailFelicitation(ResultatQuiz $resultat, float $tauxReussite): void
    {
        try {
            $apprenant = $resultat->getApprenant();
            $quiz = $resultat->getQuiz();

            if (!$apprenant) {
                $this->logger->error("❌ EmailFelicitaion: Apprenant non trouvé");
                return;
            }

            if (!$quiz) {
                $this->logger->error("❌ EmailFelicitation: Quiz non trouvé");
                return;
            }

            $prenom = $apprenant->getPrenom();
            $nom = $apprenant->getNom();
            $email = $apprenant->getEmail();
            $nomQuiz = $quiz->getTitre();
            $score = round($tauxReussite, 2);
            $bonnesReponses = $resultat->getNombreBonnesReponses();
            $totalQuestions = $resultat->getNombreTotalQuestions();

            $this->logger->info("🔍 Préparation email: Email={$email}, Prenom={$prenom}, Score={$score}%");

            // Vérifier si l'email est valide
            if (empty($email)) {
                $this->logger->error("❌ EmailFelicitation: Email de l'apprenant est vide");
                return;
            }

            // Créer le contenu HTML de l'email
            $htmlContent = $this->creerTemplateEmailFelicitation(
                $prenom,
                $nomQuiz,
                $score,
                $bonnesReponses,
                $totalQuestions
            );

            // Créer et envoyer l'email
            $sujet = "Félicitations ! Vous avez réussi le quiz « {$nomQuiz} »";

            $this->logger->info("📧 Création de l'email: À={$email}, Sujet={$sujet}");

            $emailMessage = $this->emailSender->createEmail(
                $email,
                "{$prenom} {$nom}",
                $sujet,
                $htmlContent,
                $this->creerTemplateEmailFelicitationText($prenom, $nomQuiz, $score, $bonnesReponses, $totalQuestions)
            );

            $this->logger->info("📤 Envoi de l'email via SendGrid...");

            $this->emailSender->send($emailMessage);

            $this->logger->info("✅ Email de félicitation envoyé avec succès à {$email}");
        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur lors de l'envoi de l'email: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Analyse complète du résultat du quiz
     */
    public function analyserResultat(ResultatQuiz $resultat): array
    {
        $detailsReponses = json_decode($resultat->getDetailsReponses(), true);

        if (!$detailsReponses) {
            $detailsReponses = [];
        }

        $analyse = [
            'note' => $resultat->getNote(),
            'reussi' => $resultat->isReussi(),
            'nombreBonnesReponses' => $resultat->getNombreBonnesReponses(),
            'nombreTotalQuestions' => $resultat->getNombreTotalQuestions(),
            'erreurs' => [],
            'pointsForts' => [],
            'recommandations' => []
        ];

        $nbErreurs = 0;
        $pointsManques = 0;
        $pointsObtenus = 0;

        foreach ($detailsReponses as $detail) {
            $question = $this->questionRepository->find($detail['question_id']);

            if (!$question) {
                continue;
            }

            if (!$detail['correct']) {
                $nbErreurs++;
                $pointsManques += $question->getPoints();

                $reponseCorrecte = null;
                if (isset($detail['reponse_correcte']) && $detail['reponse_correcte']) {
                    $reponseCorrecte = $this->reponseRepository->find($detail['reponse_correcte']);
                }

                $reponseUtilisateur = null;
                if (isset($detail['reponse_utilisateur']) && $detail['reponse_utilisateur']) {
                    $reponseUtilisateur = $this->reponseRepository->find($detail['reponse_utilisateur']);
                }

                $erreur = [
                    'question' => $question->getEnonce(),
                    'type_question' => $question->getType(),
                    'reponse_donnee' => $reponseUtilisateur ? $reponseUtilisateur->getTexte() : 'Aucune réponse',
                    'reponse_correcte' => $reponseCorrecte ? $reponseCorrecte->getTexte() : 'Non définie',
                    'explication_question' => $question->getExplication(),
                    'explications_detaillees' => $question->getExplicationsDetaillees(),
                    'explication_reponse_correcte' => $reponseCorrecte ? $reponseCorrecte->getExplicationReponse() : null,
                    'points' => $question->getPoints(),
                    'source_explication' => null
                ];

                $analyse['erreurs'][] = $erreur;
            } else {
                $pointsObtenus += $question->getPoints();
                $analyse['pointsForts'][] = [
                    'question' => $question->getEnonce(),
                    'points' => $question->getPoints()
                ];
            }
        }

        // Génération des recommandations
        $analyse['recommandations'] = $this->genererRecommandations(
            $analyse,
            $nbErreurs,
            $resultat,
            $pointsManques,
            $pointsObtenus
        );

        // Envoyer email de félicitation si taux de réussite >= 80%
        $tauxReussite = ($resultat->getNombreBonnesReponses() / $resultat->getNombreTotalQuestions()) * 100;
        
        $this->logger->info("📊 Analyse Quiz terminé: Score={$tauxReussite}%");

        if ($tauxReussite >= 80) {
            $this->logger->info("✅ Score suffisant (>= 80%), tentative d'envoi d'email...");
            $this->envoyerEmailFelicitation($resultat, $tauxReussite);
        } else {
            $this->logger->info("⚠️ Score insuffisant pour l'email (< 80%)");
        }

        return $analyse;
    }

    /**
     * Génère des recommandations personnalisées
     */
    private function genererRecommandations(
        array $analyse,
        int $nbErreurs,
        ResultatQuiz $resultat,
        int $pointsManques,
        int $pointsObtenus
    ): array {
        $recommandations = [];
        $tauxReussite = ($resultat->getNombreBonnesReponses() / $resultat->getNombreTotalQuestions()) * 100;

        // Recommandation basée sur le taux de réussite
        //
        if ($tauxReussite < 50) {
            $recommandations[] = [
                'niveau' => 'urgent',
                'message' => 'Votre score de ' . round($tauxReussite, 1) . '% indique que vous devez revoir les fondamentaux de ce sujet. Je vous recommande fortement de reprendre les leçons depuis le début.',
                'actions' => [
                    'Relire attentivement toutes les leçons de la formation',
                    'Prendre des notes détaillées sur les concepts clés',
                    'Faire des exercices pratiques supplémentaires',
                    'Demander de l\'aide à votre formateur si nécessaire',
                    'Refaire le quiz après une révision complète'
                ]
            ];
        } elseif ($tauxReussite < 70) {
            $recommandations[] = [
                'niveau' => 'attention',
                'message' => 'Avec ' . round($tauxReussite, 1) . '%, vous avez une compréhension partielle du sujet. Concentrez-vous sur les points d\'erreur identifiés ci-dessus pour progresser.',
                'actions' => [
                    'Revoir attentivement les sections liées aux questions erronées',
                    'Pratiquer avec des exercices supplémentaires sur ces thèmes',
                    'Discuter des concepts difficiles avec d\'autres apprenants',
                    'Refaire le quiz pour améliorer votre score',
                    'Passer plus de temps sur les points faibles'
                ]
            ];
        } elseif ($tauxReussite < 90) {
            $recommandations[] = [
                'niveau' => 'bien',
                'message' => 'Bon travail ! Vous maîtrisez la plupart des concepts avec ' . round($tauxReussite, 1) . '%. Quelques révisions ciblées vous permettront d\'atteindre l\'excellence.',
                'actions' => [
                    'Revoir uniquement les questions que vous avez manquées',
                    'Approfondir les explications fournies pour ces questions',
                    'Vous pouvez passer au module suivant',
                    'Continuez à maintenir ce bon niveau de travail'
                ]
            ];
        } else {
            $recommandations[] = [
                'niveau' => 'excellent',
                'message' => 'Excellent ! Vous maîtrisez parfaitement ce sujet avec ' . round($tauxReussite, 1) . '%. Continuez sur cette lancée !',
                'actions' => [
                    'Vous pouvez passer sereinement au module suivant',
                    'Proposez votre aide à d\'autres apprenants si possible',
                    'Approfondissez vos connaissances avec du contenu avancé',
                    'Félicitations pour ce résultat remarquable !'
                ]
            ];
        }

        // Recommandations spécifiques si des erreurs
        if ($nbErreurs > 0) {
            $conseilsPersonnalises = $this->genererConseilsPersonnalises($analyse['erreurs']);

            $recommandations[] = [
                'niveau' => 'conseil',
                'message' => "J'ai identifié {$nbErreurs} erreur(s) dans vos réponses. Voici mes conseils personnalisés pour chaque erreur :",
                'actions' => $conseilsPersonnalises
            ];
        }

        // Recommandation sur les points forts
        if (count($analyse['pointsForts']) > 0) {
            $recommandations[] = [
                'niveau' => 'pointsForts',
                'message' => "Vous avez excellé sur " . count($analyse['pointsForts']) . " question(s) ! Voici vos domaines de maîtrise :",
                'actions' => $this->genererPointsForts($analyse['pointsForts'])
            ];
        }

        return $recommandations;
    }

    /**
     * Génère une clé de cache unique pour une question
     */
    private function genererCleCachePuissance(string $question, string $reponseUtilisateur, string $reponseCorrecte): string
    {
        return md5($question . '||' . $reponseUtilisateur . '||' . $reponseCorrecte);
    }

    /**
     * Obtient une explication depuis le cache local
     */
    private function obtenirExplicationCachee(string $cleCache): ?array
    {
        $fichierCache = $this->obtenirCheminCacheExplication($cleCache);

        if (file_exists($fichierCache)) {
            $donnees = json_decode(file_get_contents($fichierCache), true);
            if ($donnees && isset($donnees['texte']) && isset($donnees['source'])) {
                $this->logger->debug("Explication récupérée du cache: $cleCache");
                return $donnees;
            }
        }

        return null;
    }

    /**
     * Sauvegarde une explication dans le cache local
     */
    private function sauvegarderExplicationCachee(string $cleCache, array $explication): void
    {
        try {
            $fichierCache = $this->obtenirCheminCacheExplication($cleCache);
            $dossier = dirname($fichierCache);

            if (!is_dir($dossier)) {
                mkdir($dossier, 0755, true);
            }

            file_put_contents($fichierCache, json_encode($explication));
            $this->logger->debug("Explication mise en cache: $cleCache");
        } catch (\Exception $e) {
            $this->logger->warning("Impossible de mettre en cache l'explication: " . $e->getMessage());
        }
    }

    /**
     * Chemin du fichier cache
     */
    private function obtenirCheminCacheExplication(string $cleCache): string
    {
        return sys_get_temp_dir() . '/gemini_cache_' . $cleCache . '.json';
    }

    /**
     * Appelle l'API Gemini pour générer une explication personnalisée
     * Retourne un tableau ['texte' => explication, 'source' => source_model]
     */
    private function genererExplicationParGemini(
        string $question,
        string $reponseUtilisateur,
        string $reponseCorrecte
    ): ?array {
        if (empty($this->geminiApiKey)) {
            return null;
        }

        // Vérifier le cache d'abord
        $cleCache = $this->genererCleCachePuissance($question, $reponseUtilisateur, $reponseCorrecte);
        $explicationCachee = $this->obtenirExplicationCachee($cleCache);

        if ($explicationCachee) {
            return $explicationCachee;
        }

        try {
            // Prompt simplifié et direct - évite la troncation des réponses
            $prompt = "Question: {$question}\n\n"
                . "Mauvaise réponse de l'étudiant: {$reponseUtilisateur}\n"
                . "Bonne réponse: {$reponseCorrecte}\n\n"
                . "Explique en 2-3 phrases pourquoi cette réponse est incorrecte et quelle est la correcte. "
                . "Utilise un ton bienveillant et pédagogique.";

            // Essayer avec gemini-2.5-flash d'abord, puis fallback à gemini-2.0-flash
            $models = ['gemini-2.5-flash', 'gemini-2.0-flash'];
            $lastException = null;
            $modelUsed = null;

            foreach ($models as $model) {
                try {
                    $response = $this->httpClient->request('POST', "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent", [
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'json' => [
                            'contents' => [
                                [
                                    'parts' => [
                                        [
                                            'text' => $prompt
                                        ]
                                    ]
                                ]
                            ],
                            'generationConfig' => [
                                'maxOutputTokens' => 500,
                                'temperature' => 0.7,
                            ]
                        ],
                        'query' => [
                            'key' => $this->geminiApiKey
                        ]
                    ]);

                    $data = $response->toArray();

                    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        $explication = trim($data['candidates'][0]['content']['parts'][0]['text']);

                        // Validation: Vérifier que l'explication est complète et utile (minimum 20 mots)
                        $nbMots = str_word_count($explication);
                        if ($nbMots < 20) {
                            // Explication trop courte, essayer le modèle suivant
                            $this->logger->warning("Explication {$model} trop courte ({$nbMots} mots), essai modèle suivant");
                            continue;
                        }

                        $this->logger->info("Explication {$model} générée avec succès ({$nbMots} mots)");
                        $resultat = [
                            'texte' => $explication,
                            'source' => ucfirst(str_replace('-', ' ', $model))
                        ];

                        // Mettre en cache pour réutilisation
                        $this->sauvegarderExplicationCachee($cleCache, $resultat);

                        return $resultat;
                    }
                } catch (\Exception $e) {
                    // Sauvegarder l'exception et essayer le modèle suivant
                    $lastException = $e;
                    $this->logger->debug("Erreur avec {$model}: " . $e->getMessage());
                    continue;
                }
            }

            // Aucun modèle n'a fonctionné
            if ($lastException) {
                $this->logger->warning('Tous les modèles Gemini échoués: ' . $lastException->getMessage());
            } else {
                $this->logger->warning('Aucun modèle Gemini n\'a retourné une réponse valide');
            }

            return null;
        } catch (\Exception $e) {
            $this->logger->warning('Erreur lors de l\'appel à Gemini: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Génère des conseils personnalisés pour chaque erreur
     */
    private function genererConseilsPersonnalises(array $erreurs): array
    {
        $conseils = [];

        foreach ($erreurs as $index => $erreur) {
            $numeroErreur = $index + 1;
            $questionCourte = mb_substr($erreur['question'], 0, 100);

            if (strlen($erreur['question']) > 100) {
                $questionCourte .= '...';
            }

            $conseil = "Erreur #{$numeroErreur} : « {$questionCourte} »";

            // Essayer d'obtenir une explication via Gemini
            $explicationGemini = $this->genererExplicationParGemini(
                $erreur['question'],
                $erreur['reponse_donnee'],
                $erreur['reponse_correcte']
            );

            if ($explicationGemini) {
                $texte = $explicationGemini['texte'];
                $source = $explicationGemini['source'];

                // Ajouter la source du modèle
                $erreur['source_explication'] = $source;

                $conseil .= " - " . mb_substr($texte, 0, 500);
                if (strlen($texte) > 500) {
                    $conseil .= '...';
                }
                $conseil .= " 🤖 $source";
            } elseif ($erreur['explications_detaillees']) {
                $erreur['source_explication'] = '';
                $conseil .= " - " . mb_substr($erreur['explications_detaillees'], 0, 500);
                if (strlen($erreur['explications_detaillees']) > 500) {
                    $conseil .= '...';
                }
                $conseil .= " 📚 ";
            } elseif ($erreur['explication_question']) {
                $erreur['source_explication'] = '';
                $conseil .= " - " . mb_substr($erreur['explication_question'], 0, 500);
                if (strlen($erreur['explication_question']) > 500) {
                    $conseil .= '...';
                }
                $conseil .= " 📚 ";
            } else {
                $erreur['source_explication'] = 'Défaut';
                $conseil .= " - Relisez attentivement cette question et sa correction.";
            }

            $conseils[] = $conseil;
        }

        if (empty($conseils)) {
            $conseils[] = "Relisez attentivement les explications fournies pour chaque question manquée.";
        }

        return $conseils;
    }

    /**
     * Génère un résumé des points forts
     */
    private function genererPointsForts(array $pointsForts): array
    {
        $resume = [];

        foreach ($pointsForts as $index => $pointFort) {
            if ($index < 3) { // Limiter à 3 exemples
                $questionCourte = mb_substr($pointFort['question'], 0, 70);
                if (strlen($pointFort['question']) > 70) {
                    $questionCourte .= '...';
                }
                $resume[] = "✓ {$questionCourte} ({$pointFort['points']} points)";
            }
        }

        if (count($pointsForts) > 3) {
            $resume[] = "Et " . (count($pointsForts) - 3) . " autre(s) question(s) réussie(s) !";
        }

        return $resume;
    }

    /**
     * Génère un rapport complet au format texte
     */
    public function genererRapportComplet(ResultatQuiz $resultat): string
    {
        $analyse = $this->analyserResultat($resultat);

        $rapport = "═══════════════════════════════════════════════════════════\n";
        $rapport .= "           📊 RAPPORT D'ANALYSE DE VOTRE QUIZ\n";
        $rapport .= "═══════════════════════════════════════════════════════════\n";
        $rapport .= "🤖 Assisté par IA (Gemini 2.5-Flash)\n";
        $rapport .= "═══════════════════════════════════════════════════════════\n\n";

        $rapport .= "Quiz : " . $resultat->getQuiz()->getTitre() . "\n";
        $rapport .= "Date : " . $resultat->getDateTentative()->format('d/m/Y à H:i') . "\n";
        $rapport .= "Apprenant : " . $resultat->getApprenant()->getPrenom() . " " . $resultat->getApprenant()->getNom() . "\n\n";

        $rapport .= "───────────────────────────────────────────────────────────\n";
        $rapport .= "🎯 RÉSULTAT GLOBAL\n";
        $rapport .= "───────────────────────────────────────────────────────────\n\n";

        $rapport .= "Score obtenu : {$analyse['note']}%\n";
        $rapport .= "Bonnes réponses : {$analyse['nombreBonnesReponses']}/{$analyse['nombreTotalQuestions']}\n";
        $rapport .= "Statut : " . ($analyse['reussi'] ? "✓ RÉUSSI" : "✗ NON RÉUSSI") . "\n\n";

        if (!empty($analyse['erreurs'])) {
            $rapport .= "═══════════════════════════════════════════════════════════\n";
            $rapport .= "❌ ANALYSE DÉTAILLÉE DES ERREURS (" . count($analyse['erreurs']) . ")\n";
            $rapport .= "═══════════════════════════════════════════════════════════\n\n";

            foreach ($analyse['erreurs'] as $index => $erreur) {
                $rapport .= "─── Erreur #" . ($index + 1) . " ───\n";
                $rapport .= "Question : {$erreur['question']}\n";
                $rapport .= "Votre réponse : {$erreur['reponse_donnee']}\n";
                $rapport .= "Réponse correcte : {$erreur['reponse_correcte']}\n";
                $rapport .= "Points perdus : {$erreur['points']}\n";

                // Afficher la source de l'explication
                if (isset($erreur['source_explication'])) {
                    $rapport .= "Source explication : " . $erreur['source_explication'] . "\n";
                }
                $rapport .= "\n";

                if ($erreur['explication_question']) {
                    $rapport .= "💡 Explication générale :\n";
                    $rapport .= $this->formaterTexte($erreur['explication_question']) . "\n\n";
                }

                if ($erreur['explications_detaillees']) {
                    $rapport .= "📚 Pour comprendre ss profondeur :\n";
                    $rapport .= $this->formaterTexte($erreur['explications_detaillees']) . "\n\n";
                }

                if ($erreur['explication_reponse_correcte']) {
                    $rapport .= "✓ Pourquoi cette réponse est correcte :\n";
                    $rapport .= $this->formaterTexte($erreur['explication_reponse_correcte']) . "\n\n";
                }

                $rapport .= "\n";
            }
        }

        if (!empty($analyse['pointsForts'])) {
            $rapport .= "═══════════════════════════════════════════════════════════\n";
            $rapport .= "✨ VOS POINTS FORTS\n";
            $rapport .= "═══════════════════════════════════════════════════════════\n\n";
            $rapport .= "Vous avez brillamment réussi {$analyse['nombreBonnesReponses']} question(s) !\n";
            $rapport .= "Total des points obtenus : " . array_sum(array_column($analyse['pointsForts'], 'points')) . " points\n\n";

            foreach ($analyse['pointsForts'] as $index => $pointFort) {
                if ($index < 5) { // Limiter l'affichage
                    $rapport .= "✓ " . mb_substr($pointFort['question'], 0, 80);
                    if (strlen($pointFort['question']) > 80) {
                        $rapport .= '...';
                    }
                    $rapport .= " ({$pointFort['points']} pts)\n";
                }
            }

            if (count($analyse['pointsForts']) > 5) {
                $rapport .= "... et " . (count($analyse['pointsForts']) - 5) . " autre(s) question(s) !\n";
            }
            $rapport .= "\n";
        }

        $rapport .= "═══════════════════════════════════════════════════════════\n";
        $rapport .= "💪 RECOMMANDATIONS PERSONNALISÉES\n";
        $rapport .= "═══════════════════════════════════════════════════════════\n\n";

        foreach ($analyse['recommandations'] as $reco) {
            $rapport .= "┌─ " . strtoupper($reco['niveau']) . " ─┐\n";
            $rapport .= $this->formaterTexte($reco['message']) . "\n\n";

            if (!empty($reco['actions'])) {
                $rapport .= "Actions recommandées :\n";
                foreach ($reco['actions'] as $action) {
                    $rapport .= "  • " . $this->formaterTexte($action) . "\n";
                }
                $rapport .= "\n";
            }
        }

        $rapport .= "═══════════════════════════════════════════════════════════\n";
        $rapport .= "🎯 CONCLUSION\n";
        $rapport .= "═══════════════════════════════════════════════════════════\n\n";

        if ($analyse['reussi']) {
            $rapport .= "Félicitations ! Vous avez réussi ce quiz avec brio.\n";
            $rapport .= "Votre travail et votre investissement portent leurs fruits.\n";
            $rapport .= "Continuez sur cette excellente lancée ! 🚀\n\n";
        } else {
            $rapport .= "Ne vous découragez pas ! L'apprentissage est un processus.\n";
            $rapport .= "Utilisez ce rapport pour identifier vos axes d'amélioration.\n";
            $rapport .= "Révisez les points faibles et réessayez. Vous allez y arriver ! 💪\n\n";
        }

        // Ajouter les informations sur les sources utilisées
        $nb_gemini = 0;
        $nb_base_donnees = 0;
        foreach ($analyse['erreurs'] as $erreur) {
            if (isset($erreur['source_explication'])) {
                if ($erreur['source_explication'] === 'Gemini 2.5-Flash') {
                    $nb_gemini++;
                } elseif ($erreur['source_explication'] === 'Base de données') {
                    $nb_base_donnees++;
                }
            }
        }

        if ($nb_gemini > 0 || $nb_base_donnees > 0) {
            $rapport .= "───────────────────────────────────────────────────────────\n";
            $rapport .= "📊 SOURCES DES EXPLICATIONS\n";
            $rapport .= "───────────────────────────────────────────────────────────\n";
            if ($nb_gemini > 0) {
                $rapport .= "🤖 Gemini 2.5-Flash (IA): $nb_gemini explication(s)\n";
            }
            if ($nb_base_donnees > 0) {
                $rapport .= "📚 Base de données: $nb_base_donnees explication(s)\n";
            }
            $rapport .= "\n";
        }

        $rapport .= "Ce rapport a été généré automatiquement par votre assistant\n";
        $rapport .= "d'apprentissage assisté par IA (Gemini 2.5-Flash) pour vous aider\n";
        $rapport .= "à progresser efficacement.\n\n";

        $rapport .= "═══════════════════════════════════════════════════════════\n";

        return $rapport;
    }

    /**
     * Formate le texte pour le rapport (gestion des retours à la ligne)
     */
    private function formaterTexte(string $texte, int $largeur = 70): string
    {
        $lignes = explode("\n", wordwrap($texte, $largeur, "\n", false));
        return implode("\n", $lignes);
    }



    /**
     * Crée le template HTML de l'email de félicitation
     */
    private function creerTemplateEmailFelicitation(
        string $prenom,
        string $nomQuiz,
        float $score,
        int $bonnesReponses,
        int $totalQuestions
    ): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .content {
            padding: 40px 20px;
        }
        .greeting {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
        }
        .score-box {
            background-color: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .score-value {
            font-size: 40px;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        .quiz-info {
            margin: 20px 0;
            color: #666;
        }
        .quiz-info p {
            margin: 10px 0;
            line-height: 1.6;
        }
        .congratulations {
            background-color: #e8f5e9;
            border: 1px solid #4caf50;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            color: #2e7d32;
            text-align: center;
            font-weight: bold;
        }
        .footer {
            background-color: #f5f5f5;
            padding: 20px;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Félicitations !</h1>
        </div>

        <div class="content">
            <div class="greeting">
                Bonjour {$prenom},
            </div>

            <p>Nous sommes heureux de vous annoncer que vous avez réussi le quiz avec un excellent score !</p>

            <div class="score-box">
                <p style="margin-top: 0; color: #667eea;">Résultats du quiz</p>
                <p style="margin: 10px 0;"><strong>{$nomQuiz}</strong></p>
                <div class="score-value">{$score}%</div>
                <p style="margin: 10px 0; color: #666;">{$bonnesReponses} bonnes réponses sur {$totalQuestions} questions</p>
            </div>

            <div class="congratulations">
                Vous avez dépassé la barre des 80% ! Continuez sur cette lancée ! 🚀
            </div>

            <div class="quiz-info">
                <p><strong>Qu'est-ce que cela signifie ?</strong></p>
                <p>Vous maîtrisez très bien les concepts de ce module. Vous pouvez avancer avec confiance vers les modules suivants.</p>

                <p><strong>Prochaines étapes :</strong></p>
                <ul>
                    <li>Explorez le contenu avancé du module suivant</li>
                    <li>Revisitez les concepts pour approfondir votre compréhension</li>
                    <li>Aidez vos collègues apprenants si possible</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé par le système d'apprentissage FORMINI.</p>
            <p>Si vous avez des questions, veuillez contacter votre formateur.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Crée la version texte de l'email de félicitation
     */
    private function creerTemplateEmailFelicitationText(
        string $prenom,
        string $nomQuiz,
        float $score,
        int $bonnesReponses,
        int $totalQuestions
    ): string {
        return <<<TEXT
Félicitations {$prenom} !

Nous sommes heureux de vous annoncer que vous avez réussi le quiz avec un excellent score !

═══════════════════════════════════════════
RÉSULTATS DU QUIZ
═══════════════════════════════════════════

Quiz: {$nomQuiz}
Score: {$score}%
Bonnes réponses: {$bonnesReponses}/{$totalQuestions}

Vous avez dépassé la barre des 80% ! Continuez sur cette lancée ! 🚀

═══════════════════════════════════════════

Qu'est-ce que cela signifie?
Vous maîtrisez très bien les concepts de ce module. Vous pouvez avancer avec confiance vers les modules suivants.

Prochaines étapes:
• Explorez le contenu avancé du module suivant
• Revisitez les concepts pour approfondir votre compréhension
• Aidez vos collègues apprenants si possible

───────────────────────────────────────────

Cet email a été envoyé par le système d'apprentissage FORMINI.
Si vous avez des questions, veuillez contacter votre formateur.
TEXT;
    }
}
