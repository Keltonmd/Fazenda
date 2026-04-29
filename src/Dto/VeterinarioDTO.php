<?php

namespace App\Dto;

use App\Entity\Veterinario;
use Symfony\Component\Validator\Constraints as Assert;

class VeterinarioDTO {
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Nome é obrigatório')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Nome muito curto',
        maxMessage: 'Nome muito longo'
    )]
    private ?string $nome = null;

    #[Assert\NotBlank(message: 'CRMV é obrigatório')]
    #[Assert\Length(
        min: 4,
        max: 20,
        minMessage: 'CRMV inválido',
        maxMessage: 'CRMV muito longo'
    )]
    #[Assert\Regex(
        pattern: "/^CRMV-[A-Z]{2}\s\d{4,6}$/",
        message: "CRMV inválido"
    )]
    private ?string $crmv = null;

    private array $fazendas = [];

    // Construtor

    public function __construct(?Veterinario $veterinario = null)
    {
        if ($veterinario) {
            $this->id = $veterinario->getId();
            $this->nome = $veterinario->getNome();
            $this->crmv = $veterinario->getCrmv();

            foreach ($veterinario->getFazendas() as $fazenda) {
                $this->fazendas[] = [
                    'id' => $fazenda->getId(),
                    'nome' => $fazenda->getNome(),
                ];
            }
        }
    }

    // Getters

    public function getId(): ?int {
        return $this->id;
    }

    public function getNome(): ?string {
        return $this->nome;
    }

    public function getCrmv(): ?string {
        return $this->crmv;
    }

    public function getFazendas(): array {
        return $this->fazendas;
    }
    

    // Setters

    public function setId(?int $id): void {
        $this->id = $id;
    }

    public function setNome(?string $nome): void {
        $this->nome = $nome;
    }

    public function setCrmv(?string $crmv): void {
        $this->crmv = $crmv;
    }

    public function setFazendas(array $fazendas): void {
        $this->fazendas = $fazendas;
    }

}
