<?php

namespace App\Entity\Carburant;

use App\Repository\Carburant\StationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StationRepository::class)]
class Station
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 30)]
    private $nom;

    #[ORM\Column(type: 'string', length: 30)]
    private $ville;

    #[ORM\OneToMany(mappedBy: 'station', targetEntity: Plein::class)]
    private $pleins;

    public function __construct()
    {
        $this->pleins = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): self
    {
        $this->ville = $ville;

        return $this;
    }

    /**
     * @return Collection<int, Plein>
     */
    public function getPleins(): Collection
    {
        return $this->pleins;
    }

    public function addPlein(Plein $plein): self
    {
        if (!$this->pleins->contains($plein)) {
            $this->pleins[] = $plein;
            $plein->setStation($this);
        }

        return $this;
    }

    public function setPleins(?Collection $pleins): self
    {
        $this->pleins = $pleins;

        return $this;
    }
    
    public function removePlein(Plein $plein): self
    {
        if ($this->pleins->removeElement($plein)) {
            // set the owning side to null (unless already changed)
            if ($plein->getStation() === $this) {
                $plein->setStation(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->nom . ' ' . $this->ville;
    }

    public function sortPleins(string $sort, string $order): self
    {
        $pleins = $this->getPleins()->getValues();
        $s = $this;
        usort($pleins, function ($a, $b) use ($s, $sort, $order) {
            $return = 0;
            if ($sort == 'date') {
                $return = $b->getDate() <=> $a->getDate();
            } elseif ($sort == 'prix') {
                $return = $b->getPrix() <=> $a->getPrix();
            } elseif ($sort == 'quantite') {
                $return = $b->getQuantite() <=> $a->getQuantite();
            } elseif ($sort == 'kilometrage') {
                $return = $b->getKilometrage() <=> $a->getKilometrage();
            } elseif ($sort == 'prix_au_litre') {
                $return = $b->getPrixAuLitre() <=> $a->getPrixAuLitre();
            }
            if ($order == 'asc' || $order == 'ASC') {
                $return *= -1;
            }
            return $return;
        });
        $this->setPleins(new ArrayCollection($pleins));
        return $this;
    }
}
