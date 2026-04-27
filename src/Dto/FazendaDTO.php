<?php

namespace App\Dto;

use App\Entity\Fazenda;

class FazendaDTO {
    private ?int $id = null;
    private ?string $nome = null;
    private ?string $responsavel = null;
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