<?php

namespace App\Entity;

use App\Repository\GadoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GadoRepository::class)]
class Gado
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $codigo = null;

    #[ORM\Column]
    private ?float $leite = null;

    #[ORM\Column]
    private ?float $racao = null;

    #[ORM\Column]
    private ?float $peso = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $nascimento = null;

    #[ORM\Column]
    private ?bool $abatido = null;

    #[ORM\ManyToOne(inversedBy: 'gados')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Fazenda $fazenda = null;

    // Construtor

    public function __construct()
    {
        $this->abatido = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodigo(): ?int
    {
        return $this->codigo;
    }

    public function setCodigo(int $codigo): static
    {
        $this->codigo = $codigo;

        return $this;
    }

    public function getLeite(): ?float
    {
        return $this->leite;
    }

    public function setLeite(float $leite): static
    {
        $this->leite = $leite;

        return $this;
    }

    public function getRacao(): ?float
    {
        return $this->racao;
    }

    public function setRacao(float $racao): static
    {
        $this->racao = $racao;

        return $this;
    }

    public function getPeso(): ?float
    {
        return $this->peso;
    }

    public function setPeso(float $peso): static
    {
        $this->peso = $peso;

        return $this;
    }

    public function getNascimento(): ?\DateTimeImmutable
    {
        return $this->nascimento;
    }

    public function setNascimento(\DateTimeImmutable $nascimento): static
    {
        $this->nascimento = $nascimento;

        return $this;
    }

    public function isAbatido(): ?bool
    {
        return $this->abatido;
    }

    public function setAbatido(bool $abatido): static
    {
        $this->abatido = $abatido;

        return $this;
    }

    public function getFazenda(): ?Fazenda
    {
        return $this->fazenda;
    }

    public function setFazenda(?Fazenda $fazenda): static
    {
        $this->fazenda = $fazenda;

        return $this;
    }
}
