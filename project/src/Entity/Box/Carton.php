<?php

namespace App\Entity\Box;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\Box\CartonRepository;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: CartonRepository::class)]
class Carton
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $numero;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $emplacement;

    #[ORM\OneToMany(mappedBy: 'carton', targetEntity: Objet::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private $objets;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getEmplacement(): ?string
    {
        return $this->emplacement;
    }

    public function setEmplacement(?string $emplacement): self
    {
        $this->emplacement = $emplacement;

        return $this;
    }

    /**
     * @return Collection<int, Objet>
     */
    public function getObjets(): Collection
    {
        return $this->objets;
    }

    public function addObjet(Objet $objet): self
    {
        if (!$this->objets->contains($objet)) {
            $this->objets[] = $objet;
            $objet->setCarton($this);
        }

        return $this;
    }

    public function removeObjet(Objet $objet): self
    {
        if ($this->objets->removeElement($objet)) {
            // set the owning side to null (unless already changed)
            if ($objet->getCarton() === $this) {
                $objet->setCarton(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->numero;
    }
}
