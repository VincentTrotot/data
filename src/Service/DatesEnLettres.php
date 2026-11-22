<?php

namespace App\Service;

/** Dates en français : « jeudi 29 octobre 2026 ». */
class DatesEnLettres
{
    private const JOURS = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    private const MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre',
        'octobre', 'novembre', 'décembre'];

    public function jour(\DateTimeInterface $date): string
    {
        return \sprintf(
            '%s %s %s %s',
            self::JOURS[(int) $date->format('w')],
            1 === (int) $date->format('j') ? '1er' : $date->format('j'),
            self::MOIS[(int) $date->format('n') - 1],
            $date->format('Y'),
        );
    }
}
