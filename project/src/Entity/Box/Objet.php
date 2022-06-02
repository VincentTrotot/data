<?php

namespace App\Entity\Box;

use App\Repository\Box\ObjetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ObjetRepository::class)]
class Objet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $nom;

    #[ORM\ManyToOne(targetEntity: CategorieObjet::class, inversedBy: 'objets')]
    private $categorie;

    #[ORM\ManyToOne(targetEntity: Carton::class, inversedBy: 'objets')]
    private $carton;

    #[ORM\OneToMany(mappedBy: 'objet', targetEntity: Mouvement::class, orphanRemoval: true)]
    private $mouvements;

    public function __construct()
    {
        $this->mouvements = new ArrayCollection();
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

    public function getCategorie(): ?CategorieObjet
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieObjet $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getCarton(): ?Carton
    {
        return $this->carton;
    }

    public function setCarton(?Carton $carton): self
    {
        $this->carton = $carton;

        return $this;
    }

    /**
     * @return Collection<int, Mouvement>
     */
    public function getMouvements(): Collection
    {
        return $this->mouvements;
    }

    public function addMouvement(Mouvement $mouvement): self
    {
        if (!$this->mouvements->contains($mouvement)) {
            $this->mouvements[] = $mouvement;
            $mouvement->setObjet($this);
        }

        return $this;
    }

    public function removeMouvement(Mouvement $mouvement): self
    {
        if ($this->mouvements->removeElement($mouvement)) {
            // set the owning side to null (unless already changed)
            if ($mouvement->getObjet() === $this) {
                $mouvement->setObjet(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom;
    }

    public function isIn() : bool
    {
        $lastMouvement = $this->getMouvements()->last();
        return $lastMouvement->getSens() == 'in';
    }

}
