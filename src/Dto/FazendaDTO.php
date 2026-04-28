<?php

namespace App\Dto;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\Fazenda;

class FazendaDTO {
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Nome é obrigatório')]
    #[Assert\Length(
        min: 2,
        max: 150,
        minMessage: 'Nome muito curto',
        maxMessage: 'Nome muito longo'
    )]
    private ?string $nome = null;

    #[Assert\NotBlank(message: 'Responsável é obrigatório')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Responsável muito curto',
        maxMessage: 'Responsável muito longo'
    )]
    private ?string $responsavel = null;

    #[Assert\NotNull(message: 'Tamanho é obrigatório')]
    #[Assert\Positive(message: 'Tamanho deve ser maior que zero')]
    #[Assert\LessThan(200000, message: 'Tamanho muito grande (inválido)')]
    private ?float $tamanhoHA = null;

    // Construtor

    public function __construct(?Fazenda $fazenda = null)
    {
        if ($fazenda) {
            $this->id = $fazenda->getId();
            $this->nome = $fazenda->getNome();
            $this->responsavel = $fazenda->getResponsavel();
            $this->tamanhoHA = $fazenda->getTamanhoHA();
        }
    }

    // Getters

    public function getId(): ?int {
        return $this->id;
    }

    public function getNome(): ?string {
        return $this->nome;
    }

    public function getResponsavel(): ?string {
        return $this->responsavel;
    }


    public function getTamanhoHA(): ?float {
        return $this->tamanhoHA;
    }

    // Setters

    public function setId(?int $id): void {
        $this->id = $id;
    }

    public function setNome(?string $nome): void {
        $this->nome = $nome;
    }

    public function setResponsavel(?string $responsavel): void {
        $this->responsavel = $responsavel;
    }

    public function setTamanhoHA(?float $tamanhoHA): void {
        $this->tamanhoHA = $tamanhoHA;
    }
    
}