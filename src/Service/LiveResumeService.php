<?php

namespace App\Service;

use App\Entity\Evenement;
use App\Repository\LiveReactionRepository;
use App\Repository\LiveCommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class LiveResumeService
{
    private OllamaService $ollama;
    private LiveReactionRepository $reactionRepo;
    private LiveCommentRepository $commentRepo;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(
        OllamaService $ollama,
        LiveReactionRepository $reactionRepo,
        LiveCommentRepository $commentRepo,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        $this->ollama = $ollama;
        $this->reactionRepo = $reactionRepo;
        $this->commentRepo = $commentRepo;
        $this->em = $em;
        $this->logger = $logger;
    }

    public function generateAndSaveResume(Evenement $event): bool
    {
        try {
            // 1. Récupération des données
            $reactions = $this->reactionRepo->getReactionDistributionByEvent($event->getId());
            $totalReactions = $this->reactionRepo->countByEvent($event->getId());
            $totalComments = $this->commentRepo->countByEvent($event->getId());
            $topReactors = $this->reactionRepo->getTopReactorsByEvent($event->getId());

            // Si trop peu d'activité, on ne génère pas de résumé
            if ($totalReactions < 5 && $totalComments < 2) {
                $event->setResumeAuto("Événement terminé. Trop peu d'activité pour générer un résumé détaillé.");
                $event->setResumeGeneratedAt(new \DateTime());
                $this->em->flush();
                return false;
            }

            // 2. Construction des stats et détails pour le prompt
            $repartitionStr = "";
            $distributionData = [];
            foreach ($reactions as $r) {
                $repartitionStr .= "{$r['type']}: {$r['count']}, ";
                $distributionData[$r['type']] = (int)$r['count'];
            }

            // Récupération de la timeline pour les graphiques
            $reactionTimeline = $this->reactionRepo->getActivityTimelineByEvent($event->getId());
            $commentTimeline = $this->commentRepo->getActivityTimelineByEvent($event->getId());

            // Fusion des timelines
            $allMinutes = array_unique(array_merge(
                array_column($reactionTimeline, 'minute'),
                array_column($commentTimeline, 'minute')
            ));
            sort($allMinutes);

            $chartTimeline = [
                'labels' => $allMinutes,
                'reactions' => [],
                'comments' => []
            ];

            $reactionMap = array_column($reactionTimeline, 'count', 'minute');
            $commentMap = array_column($commentTimeline, 'count', 'minute');

            foreach ($allMinutes as $min) {
                $chartTimeline['reactions'][] = (int)($reactionMap[$min] ?? 0);
                $chartTimeline['comments'][] = (int)($commentMap[$min] ?? 0);
            }

            // Récupération des commentaires récents avec les auteurs
            $recentComments = $this->commentRepo->findRecentByEvent($event->getId(), 10);
            $commentsDetails = "";
            foreach ($recentComments as $c) {
                $userName = $c->getUser() ? $c->getUser()->getPrenom() . " " . $c->getUser()->getNom() : "Anonyme";
                $commentsDetails .= "- {$userName} a commenté : \"{$c->getContent()}\"\n";
            }

            // Récupération des réactions récentes avec les auteurs
            $recentReactions = $this->reactionRepo->findRecentByEvent($event->getId(), 10);
            $reactionsDetails = "";
            foreach ($recentReactions as $r) {
                $userName = $r->getUser() ? $r->getUser()->getPrenom() . " " . $r->getUser()->getNom() : "Anonyme";
                $reactionsDetails .= "- {$userName} a réagi avec {$r->getType()}\n";
            }

            $topReactorsStr = implode(", ", array_map(fn($u) => "{$u['prenom']} {$u['nom']}", $topReactors));

            $liveData = [
                'titre' => $event->getTitre(),
                'total_reactions' => $totalReactions,
                'repartition' => $repartitionStr,
                'total_commentaires' => $totalComments,
                'top_contributeurs' => $topReactorsStr,
                'details_commentaires' => $commentsDetails,
                'details_reactions' => $reactionsDetails,
                'chart_timeline' => $chartTimeline,
                'reaction_distribution' => $distributionData
            ];

            // 3. Appel à Ollama avec un prompt plus riche
            $prompt = "Tu es un assistant expert en communication pour une plateforme de live streaming.
            Voici les statistiques et interactions d'un live qui vient de se terminer :
            - Titre de l'événement : {$liveData['titre']}
            - Total Réactions : {$liveData['total_reactions']} ({$liveData['repartition']})
            - Total Commentaires : {$liveData['total_commentaires']}
            - Top Contributeurs (les plus actifs) : {$liveData['top_contributeurs']}

            Détails des dernières interactions :
            REACTIONS :
            {$liveData['details_reactions']}

            COMMENTAIRES :
            {$liveData['details_commentaires']}

            Génère un rapport structuré pour l'administrateur.
            Structure attendue :
            1. Un résumé global de l'ambiance (3-4 lignes).
            2. Une section 'Actions marquantes' où tu listes explicitement qui a fait quoi (ex: \"[Nom] a commenté '[Message]'\", \"[Nom] a été très réactif avec [Réaction]\").
            
            Sois précis, professionnel et chaleureux. 
            Réponds EXCLUSIVEMENT en français avec le texte du rapport.";

            $summary = $this->ollama->generateSummary($prompt);

            if (empty($summary)) {
                $summary = "Le live '{$event->getTitre()}' a été une réussite avec {$totalReactions} réactions et {$totalComments} commentaires.\n\nActions marquantes :\n- Top contributeurs : {$topReactorsStr}.\n\n(Note: Ce résumé est basé sur les statistiques car l'IA Ollama n'a pas pu être jointe pour l'analyse détaillée).";
            }

            // 4. Sauvegarde
            $event->setResumeAuto($summary);
            $event->setResumeGeneratedAt(new \DateTime());
            $event->setLiveSummaryData($liveData);
            
            $this->em->flush();
            return true;

        } catch (\Exception $e) {
            $this->logger->error("Erreur génération résumé Live {$event->getId()}: " . $e->getMessage());
            return false;
        }
    }
}
