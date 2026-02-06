<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class YouTubeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('convertYoutubeToEmbed', [$this, 'convertYoutubeToEmbed']),
        ];
    }

    public function convertYoutubeToEmbed(string $url): string
    {
        // Si c'est déjà une URL embed, la retourner telle quelle
        if (strpos($url, 'youtube.com/embed/') !== false || strpos($url, 'youtube-nocookie.com/embed/') !== false) {
            return $url;
        }

        // Extraire l'ID de la vidéo YouTube
        $videoId = null;

        // Format: https://www.youtube.com/watch?v=VIDEO_ID
        if (preg_match('/[?&]v=([^&]+)/', $url, $matches)) {
            $videoId = $matches[1];
        }
        // Format: https://youtu.be/VIDEO_ID
        elseif (preg_match('/youtu\.be\/([^?&]+)/', $url, $matches)) {
            $videoId = $matches[1];
        }
        // Format: https://www.youtube.com/embed/VIDEO_ID
        elseif (preg_match('/youtube\.com\/embed\/([^?&]+)/', $url, $matches)) {
            $videoId = $matches[1];
        }

        // Si on a trouvé un ID, retourner l'URL embed
        if ($videoId) {
            return 'https://www.youtube-nocookie.com/embed/' . $videoId;
        }

        // Sinon, retourner l'URL originale
        return $url;
    }
}