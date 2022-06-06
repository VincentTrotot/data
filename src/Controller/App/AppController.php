<?php

namespace App\Controller\App;

use Symfony\UX\Chartjs\Model\Chart;
use App\Repository\Carburant\PleinRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AppController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(PleinRepository $pleinRepository, ChartBuilderInterface $chartBuilder): Response
    {
        $pleins = $pleinRepository->findAllByDate('ASC');
        $labels = [];
        $data = [];


        foreach ($pleins as $plein) {
            $labels[] = $plein->getDate()->format('m/Y');
            $data[] = $plein->getPrixAuLitre();
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Prix du litre',
                    'backgroundColor' => 'rgb(0, 0, 75)',
                    'borderColor' => 'rgb(0, 0, 75)',
                    'data' => $data,
                ],
            ],
        ]);

        return $this->render('app/index.html.twig', [
            'chart' => $chart,
        ]);
    }
}
