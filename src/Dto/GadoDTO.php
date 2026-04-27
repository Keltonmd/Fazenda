<?php

namespace App\Dto;

use App\Entity\Gado;

class GadoDTO {
    private ?int $id = null;
    private ?int $codigo = null;
    private ?float $leite = null;
    private ?float $racao = null;
    private ?float $peso = null;
    private ?\DateTimeImmutable $nascimento = null;
    private ?bool $abatido = null;
    private ?\DateTimeImmutable $dataAbate = null;
    private ?int $fazendaId = null;

    // Construtor

    public function __construct(?Gado $gado = null)
    {
        if ($gado) {
            $this->id = $gado->getId();
            $this->codigo = $gado->getCodigo();
            $this->leite = $gado->getLeite();
            $this->racao = $gado->getRacao();
            $this->peso = $gado->getPeso();
            $this->nascimento = $gado->getNascimento();
            $this->abatido = $gado->isAbatido();
            $this->dataAbate = $gado->getDataAbate();
            $this->fazendaId = $gado->getFazenda()?->getId();
        }
    }

    // Getters

    public function getId(): ?int {
        return $this->id;
    }

    public function getCodigo(): ?int {
        return $this->codigo;
    }

    public function getLeite(): ?float {
        return $this->leite;
    }

    public function getRacao(): ?float {
        return $this->racao;
    }

    public function getPeso(): ?float {
        return $this->peso;
    }

    public function getNascimento(): ?\DateTimeImmutable {
        return $this->nascimento;
    }

    public function isAbatido(): ?bool {
        return $this->abatido;
    }

    public function getDataAbate(): ?\DateTimeImmutable {
        return $this->dataAbate;
    }

    public function getDataLimiteCancelamentoAbate(): ?\DateTimeImmutable
    {
        if ($this->dataAbate === null) {
            return null;
        }

        return $this->dataAbate->modify('+1 day');
    }

    public function getFazendaId(): ?int {
        return $this->fazendaId;
    }

    // Setters

    public function setId(?int $id): void {
        $this->id = $id;
    }

    public function setCodigo(?int $codigo): void {
        $this->codigo = $codigo;
    }

    public function setLeite(?float $leite): void {
        $this->leite = $leite;
    }

    public function setRacao(?float $racao): void {
        $this->racao = $racao;
    }

    public function setPeso(?float $peso): void {
        $this->peso = $peso;
    }

    public function setNascimento(?\DateTimeImmutable $nascimento): void {
        $this->nascimento = $nascimento;
    }

    public function setAbatido(?bool $abatido): void {
        $this->abatido = $abatido;
    }

    public function setDataAbate(?\DateTimeImmutable $dataAbate): void {
        $this->dataAbate = $dataAbate;
    }

    public function setFazendaId(?int $fazendaId): void {
        $this->fazendaId = $fazendaId;
    }

    //metodos
    public function podeCancelarAbate(): bool
    {
        if (!$this->abatido || $this->dataAbate === null) {
            return false;
        }

        return new \DateTimeImmutable() <= $this->dataAbate->modify('+1 day');
    }
}
