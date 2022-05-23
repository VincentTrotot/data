<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions() : array
    {
        return [
            new TwigFunction('order', [$this, 'orderLinks'], array('is_safe' => array('html'))),
        ];
    }

    public function orderLinks(string $field): string
    {
        $return = '';
        $sort = $_GET['sort'] ?? 'date';
        $order = $_GET['order'] ?? 'desc';
        if (($sort == $field && $order == 'asc') || $sort != $field) {
            $return .= '<a href="?sort='.$field.'&order=desc">▲</a>';
        }
        if (($sort == $field && ($order == 'desc' || $sort == null)) || $sort != $field) {
            $return .= '<a href="?sort='.$field.'&order=asc">▼</a>';
        }
        return $return;
    }
}