<?php

namespace App\Controller\App;

use App\Entity\Carburant\Plein;
use App\Entity\Carburant\Station;
use App\Entity\Carburant\Voiture;
use App\Repository\Carburant\PleinRepository;
use App\Repository\Carburant\StationRepository;
use App\Repository\Carburant\VoitureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API utilisée par le raccourci iOS : toutes les routes exigent la clé `API_KEY`
 * (dernier segment de l'URL pour les ajouts, `?cle=` pour les listes).
 * Clé fausse ou `API_KEY` vide : 404, comme si la route n'existait pas.
 */
#[Route('/api')]
class ApiController extends AbstractController
{
    public function __construct(#[Autowire('%env(API_KEY)%')] private readonly string $apiKey)
    {
    }

    #[Route('/plein/add/{quantite}/{prix}/{kilometrage}/{voiture}/{station}/{api_key}', name: 'api_plein_add', requirements: ['quantite' => '\d+', 'prix' => '\d+', 'kilometrage' => '\d+', 'voiture' => '\d+', 'station' => '\d+'], methods: ['GET'])]
    public function add(PleinRepository $pleinRepository, VoitureRepository $voitureRepository, StationRepository $stationRepository, int $quantite, int $prix, int $kilometrage, int $voiture, int $station, string $api_key): Response
    {
        $this->verifierCle($api_key);

        $voiture_ = $voitureRepository->find($voiture);
        $station_ = $stationRepository->find($station);
        if (null === $voiture_ || null === $station_) {
            return new JsonResponse(['erreur' => 'Voiture ou station inconnue'], Response::HTTP_BAD_REQUEST);
        }

        $plein = new Plein();
        $plein->setDate(new \DateTime());
        $plein->setQuantite($quantite);
        $plein->setPrix($prix);
        $plein->setKilometrage($kilometrage);
        $plein->setVoiture($voiture_);
        $plein->setStation($station_);

        $pleinRepository->add($plein, true);

        return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/stations', name: 'api_get_station', methods: ['GET'])]
    public function getStations(Request $request, StationRepository $stationRepository): Response
    {
        $this->verifierCle($request->query->getString('cle'));

        $data = [];
        foreach ($stationRepository->findAll() as $station) {
            $data[$station->getNom() . ' ' . $station->getVille()] = $station->getId();
        }

        return $this->json($data);
    }

    #[Route('/voitures', name: 'api_get_voitures', methods: ['GET'])]
    public function getVoitures(Request $request, VoitureRepository $voitureRepository): Response
    {
        $this->verifierCle($request->query->getString('cle'));

        $data = [];
        foreach ($voitureRepository->findAll() as $voiture) {
            $data[$voiture->getMarque() . ' ' . $voiture->getModele()] = $voiture->getId();
        }

        return $this->json($data);
    }

    #[Route('/voiture/add/{marque}/{modele}/{immatriculation}/{api_key}', name: 'api_voiture_add', methods: ['GET'])]
    public function addVoiture(VoitureRepository $voitureRepository, string $marque, string $modele, string $immatriculation, string $api_key): Response
    {
        $this->verifierCle($api_key);

        $voiture = new Voiture();
        $voiture->setMarque($marque);
        $voiture->setModele($modele);
        $voiture->setImmatriculation($immatriculation);

        $voitureRepository->add($voiture, true);

        return $this->json($voiture->getId());
    }

    #[Route('/station/add/{nom}/{ville}/{api_key}', name: 'api_station_add', methods: ['GET'])]
    public function addStation(StationRepository $stationRepository, string $nom, string $ville, string $api_key): Response
    {
        $this->verifierCle($api_key);

        $station = new Station();
        $station->setNom($nom);
        $station->setVille($ville);

        $stationRepository->add($station, true);

        return $this->json($station->getId());
    }

    private function verifierCle(string $cle): void
    {
        if ('' === $this->apiKey || !hash_equals($this->apiKey, $cle)) {
            throw new NotFoundHttpException();
        }
    }
}
