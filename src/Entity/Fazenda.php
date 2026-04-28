<?php

namespace App\Entity;

use App\Repository\FazendaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FazendaRepository::class)]
#[ORM\UniqueConstraint(name: "unique_usuario_nome", columns: ["usuario_id", "nome"])]
class Fazenda
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $nome = null;

    #[ORM\Column(length: 100)]
    private ?string $responsavel = null;

    #[ORM\Column]
    private ?float $tamanhoHA = null;

    /**
     * @var Collection<int, Veterinario>
     */
    #[ORM\ManyToMany(targetEntity: Veterinario::class, inversedBy: 'fazendas')]
    private Collection $veterinarios;

    #[ORM\ManyToOne(inversedBy: 'fazendas')]
    private ?Usuario $usuario = null;

    /**
     * @var Collection<int, Gado>
     */
    #[ORM\OneToMany(targetEntity: Gado::class, mappedBy: 'fazenda', orphanRemoval: true)]
    private Collection $gados;

    public function __construct()
    {
        $this->veterinarios = new ArrayCollection();
        $this->gados = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): ?string
    {
        return $this->nome;
    }

    public function setNome(?string $nome): static
    {
        $this->nome = $nome;

        return $this;
    }

    public function getResponsavel(): ?string
    {
        return $this->responsavel;
    }

    public function setResponsavel(?string $responsavel): static
    {
        $this->responsavel = $responsavel;

        return $this;
    }

    public function getTamanhoHA(): ?float
    {
        return $this->tamanhoHA;
    }

    public function setTamanhoHA(float $tamanhoHA): static
    {
        $this->tamanhoHA = $tamanhoHA;

        return $this;
    }

    /**
     * @return Collection<int, Veterinario>
     */
    public function getVeterinarios(): Collection
    {
        return $this->veterinarios;
    }

    public function addVeterinario(Veterinario $veterinario): static
    {
        if (!$this->veterinarios->contains($veterinario)) {
            $this->veterinarios->add($veterinario);
        }

        return $this;
    }

    public function removeVeterinario(Veterinario $veterinario): static
    {
        $this->veterinarios->removeElement($veterinario);

        return $this;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuario $usuario): static
    {
        $this->usuario = $usuario;

        return $this;
    }

    /**
     * @return Collection<int, Gado>
     */
    public function getGados(): Collection
    {
        return $this->gados;
    }

    public function addGado(Gado $gado): static
    {
        if (!$this->gados->contains($gado)) {
            $this->gados->add($gado);
            $gado->setFazenda($this);
        }

        return $this;
    }

    public function removeGado(Gado $gado): static
    {
        if ($this->gados->removeElement($gado)) {
            // set the owning side to null (unless already changed)
            if ($gado->getFazenda() === $this) {
                $gado->setFazenda(null);
            }
        }

        return $this;
    }
}
