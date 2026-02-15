<?php

namespace App\Service;

use App\Entity\ResultatQuiz;
use App\Repository\QuestionRepository;
use App\Repository\ReponseRepository;

class ChatbotAnalyseService
{
    private QuestionRepository $questionRepository;
    private ReponseRepository $reponseRepository;

    public function __construct(
        QuestionRepository $questionRepository,
        ReponseRepository $reponseRepository
    ) {
        $this->questionRepository = $questionRepository;
        $this->reponseRepository = $reponseRepository;
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
                    'points' => $question->getPoints()
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
     * Génère des conseils personnalisés pour chaque erreur
     */
    private function genererConseilsPersonnalises(array $erreurs): array
    {
        $conseils = [];

        foreach ($erreurs as $index => $erreur) {
            $numeroErreur = $index + 1;
            $questionCourte = mb_substr($erreur['question'], 0, 60);

            if (strlen($erreur['question']) > 60) {
                $questionCourte .= '...';
            }

            $conseil = "Erreur #{$numeroErreur} : « {$questionCourte} »";

            if ($erreur['explications_detaillees']) {
                $conseil .= " - " . mb_substr($erreur['explications_detaillees'], 0, 150);
                if (strlen($erreur['explications_detaillees']) > 150) {
                    $conseil .= '...';
                }
            } elseif ($erreur['explication_question']) {
                $conseil .= " - " . mb_substr($erreur['explication_question'], 0, 150);
                if (strlen($erreur['explication_question']) > 150) {
                    $conseil .= '...';
                }
            } else {
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
                $rapport .= "Points perdus : {$erreur['points']}\n\n";

                if ($erreur['explication_question']) {
                    $rapport .= "💡 Explication générale :\n";
                    $rapport .= $this->formaterTexte($erreur['explication_question']) . "\n\n";
                }

                if ($erreur['explications_detaillees']) {
                    $rapport .= "📚 Pour comprendre en profondeur :\n";
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

        $rapport .= "Ce rapport a été généré automatiquement par votre assistant\n";
        $rapport .= "d'apprentissage pour vous aider à progresser efficacement.\n\n";

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
}
