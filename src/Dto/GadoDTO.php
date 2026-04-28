<?php

namespace App\Dto;

use App\Entity\Gado;
use Symfony\Component\Validator\Constraints as Assert;

class GadoDTO {
    private ?int $id = null;

    #[Assert\NotNull(message: 'Código é obrigatório')]
    #[Assert\Positive(message: 'Código deve ser positivo')]
    private ?int $codigo = null;

    #[Assert\NotNull(message: 'Leite é obrigatório')]
    #[Assert\GreaterThanOrEqual(0, message: 'Leite não pode ser negativo')]
    private ?float $leite = null;

    #[Assert\NotNull(message: 'Ração é obrigatória')]
    #[Assert\GreaterThanOrEqual(0, message: 'Ração não pode ser negativa')]
    private ?float $racao = null;

    #[Assert\NotNull(message: 'Peso é obrigatório')]
    #[Assert\GreaterThan(0, message: 'Peso deve ser maior que zero')]
    #[Assert\LessThan(2000, message: 'Peso muito alto (inválido)')]
    private ?float $peso = null;

    #[Assert\NotNull(message: 'Nascimento é obrigatório')]
    #[Assert\Type(\DateTimeImmutable::class, message: 'Data inválida')]
    #[Assert\LessThanOrEqual('today', message: 'Data de nascimento não pode ser futura')]
    private ?\DateTimeImmutable $nascimento = null;

    private ?bool $abatido = null;

    private ?\DateTimeImmutable $dataAbate = null;

    #[Assert\Positive(message: 'Fazenda inválida')]
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
