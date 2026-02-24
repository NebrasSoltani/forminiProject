<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class LanguageExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('language_switcher', [$this, 'renderLanguageSwitcher'], ['is_safe' => ['html']]),
        ];
    }

    public function renderLanguageSwitcher(string $currentLocale = 'fr'): string
    {
        $languages = [
            'fr' => [
                'name' => 'Français',
                'flag' => '🇫🇷'
            ],
            'en' => [
                'name' => 'English',
                'flag' => '🇬🇧'
            ]
        ];

        $html = '<div class="language-switcher dropdown">';
        $html .= '<button class="btn btn-link dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">';
        $html .= $languages[$currentLocale]['flag'] . ' ' . $languages[$currentLocale]['name'];
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu" aria-labelledby="languageDropdown">';

        foreach ($languages as $locale => $lang) {
            if ($locale !== $currentLocale) {
                $html .= '<li><a class="dropdown-item" href="/locale/' . $locale . '">';
                $html .= $lang['flag'] . ' ' . $lang['name'];
                $html .= '</a></li>';
            }
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
}
