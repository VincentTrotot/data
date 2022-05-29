<?php

namespace App\Controller;

use App\Entity\Plein;
use App\Entity\Station;
use App\Entity\Voiture;
use App\Repository\PleinRepository;
use App\Repository\StationRepository;
use App\Repository\VoitureRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/plein/add/{quantite}/{prix}/{kilometrage}/{voiture}/{station}', name: 'api_plein_add', methods: ['GET'])]
    public function add(PleinRepository $pleinRepository, VoitureRepository $voitureRepository, StationRepository $stationRepository, $quantite, $prix, $kilometrage, $voiture, $station): Response
    {
        $voiture_ = $voitureRepository->find($voiture);
        $station_ = $stationRepository->find($station);
        $plein = new Plein();
        $plein->setDate(new \DateTime());
        $plein->setQuantite($quantite);
        $plein->setPrix($prix);
        $plein->setKilometrage($kilometrage);
        if($voiture_ != null)
            $plein->setVoiture($voiture_);
        if($station_ != null)
            $plein->setStation($station_);

        $pleinRepository->add($plein, true);

        return $this->redirectToRoute('app_main', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/stations', name: 'api_get_station', methods: ['GET'])]
    public function getStations(StationRepository $stationRepository): Response
    {
        $data = [];
        $stations = $stationRepository->findAll();
        foreach ($stations as $station) {
            $data[$station->getNom(). ' ' . $station->getVille()] = $station->getId();
        }
        return $this->json($data);

    }

    #[Route('/voitures', name: 'api_get_voitures', methods: ['GET'])]
    public function getVoitures(VoitureRepository $voitureRepository): Response
    {
        $data = [];
        $voitures = $voitureRepository->findAll();
        foreach ($voitures as $voiture) {
            $data[$voiture->getMarque() . ' ' . $voiture->getModele()] = $voiture->getId();
        }
        return $this->json($data);

    }

    #[Route('/voiture/add/{marque}/{modele}/{immatriculation}', name: 'api_voiture_add', methods: ['GET'])]
    public function addVoiture(VoitureRepository $voitureRepository, string $marque, string $modele, string $immatriculation): Response
    {
        $voiture = new Voiture();
        $voiture->setMarque($marque);
        $voiture->setModele($modele);
        $voiture->setImmatriculation($immatriculation);
        
        $voitureRepository->add($voiture, true);

        return $this->json($voiture->getId());

       
    }

    #[Route('/station/add/{nom}/{ville}', name: 'api_station_add', methods: ['GET'])]
    public function addStation(StationRepository $stationRepository, string $nom, string $ville): Response
    {

        $station = new Station();
        $station->setNom($nom);
        $station->setVille($ville);

        $stationRepository->add($station, true);

        return $this->json($station->getId());

    }
}
