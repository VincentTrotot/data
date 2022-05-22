<?php

namespace App\Entity;

use App\Repository\VoitureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoitureRepository::class)]
class Voiture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;


    #[ORM\OneToMany(mappedBy: 'voiture', targetEntity: Plein::class)]
    #[ORM\OrderBy(['date' => 'DESC'])]
    private $pleins;

    #[ORM\Column(type: 'string', length: 30)]
    private $marque;

    #[ORM\Column(type: 'string', length: 30)]
    private $modele;

    #[ORM\Column(type: 'string', length: 10)]
    private $immatriculation;

    public function __construct()
    {
        $this->pleins = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
            $plein->setVoiture($this);
        }

        return $this;
    }

    public function removePlein(Plein $plein): self
    {
        if ($this->pleins->removeElement($plein)) {
            // set the owning side to null (unless already changed)
            if ($plein->getVoiture() === $this) {
                $plein->setVoiture(null);
            }
        }

        return $this;
    }

    public function setPleins(?Collection $pleins): self
    {
        $this->pleins = $pleins;

        return $this;
    }

    public function getMarque(): ?string
    {
        return $this->marque;
    }

    public function setMarque(string $marque): self
    {
        $this->marque = $marque;

        return $this;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(string $modele): self
    {
        $this->modele = $modele;

        return $this;
    }

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(string $immatriculation): self
    {
        $this->immatriculation = $immatriculation;

        return $this;
    }

    public function __toString()
    {
        return $this->marque . ' ' . $this->modele;
    }

    public function getMontantTotal(): ?int
    {
        $total = 0;
        foreach ($this->pleins as $plein) {
            $total += $plein->getPrix();
        }
        return $total;
    }

    public function getKilometresParcourus(Plein $plein): ?int
    {
        $pleins = $this->getPleins()->getValues();
        usort($pleins, function ($a, $b) {
            return $b->getDate() <=> $a->getDate();
        });
        foreach ($pleins as $key => $p) {
            if ($p->getId() == $plein->getId()) {
                if ($key == 0) {
                    return 0;
                } else {
                    return $pleins[$key - 1]->getKilometrage() - $p->getKilometrage();
                }
            }
        }

    }

    public function getKilometresTotal(): ?int
    {
        $pleins = $this->getPleins()->getValues();
        usort($pleins, function ($a, $b) {
            return $b->getDate() <=> $a->getDate();
        });
        return $pleins[0]->getKilometrage() - $pleins[count($pleins) - 1]->getKilometrage();
    }

    public function getPrixMoyenPlein(): ?int
    {
        $pleins = $this->getPleins();
        $total = 0;
        foreach ($pleins as $plein) {
            $total += $plein->getPrix();
        }
        return $total / $pleins->count();
    }

    public function getPrixMoyenLitre(): ?int
    {
        $pleins = $this->getPleins();
        $prixTotal = 0;
        $quantiteTotal = 0;
        foreach ($pleins as $plein) {
            $prixTotal += $plein->getPrix();
            $quantiteTotal += $plein->getQuantite();
        }
        return $prixTotal / $quantiteTotal * 100;
    }

    public function sortPleins(string $sort, string $order): self
    {
        $pleins = $this->getPleins()->getValues();
        $v = $this;
        usort($pleins, function ($a, $b) use ($v, $sort, $order) {
            $return = 0;
            if ($sort == 'date') {
                $return = $b->getDate() <=> $a->getDate();
            } elseif ($sort == 'prix') {
                $return = $b->getPrix() <=> $a->getPrix();
            } elseif ($sort == 'quantite') {
                $return = $b->getQuantite() <=> $a->getQuantite();
            } elseif ($sort == 'kilometrage') {
                $return = $b->getKilometrage() <=> $a->getKilometrage();
            } elseif ($sort == 'station') {
                $return = $b->getStation()?->getNom() <=> $a->getStation()?->getNom();
            } elseif ($sort == 'prix_au_litre') {
                $return = $b->getPrixAuLitre() <=> $a->getPrixAuLitre();
            } elseif ($sort == 'kilometre_parcourus') {
                $return = $v->getKilometresParcourus($b) <=> $v->getKilometresParcourus($a);
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
