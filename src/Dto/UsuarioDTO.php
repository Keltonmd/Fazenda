<?php

namespace App\Dto;

use App\Entity\Usuario;
use Symfony\Component\Validator\Constraints as Assert;

class UsuarioDTO {
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Nome é obrigatório')]
    #[Assert\Length(
        min: 2,
        max: 150,
        minMessage: 'Nome muito curto',
        maxMessage: 'Nome muito longo'
    )]
    private ?string $nome = null;

    #[Assert\NotBlank(message: 'E-mail é obrigatório')]
    #[Assert\Email(message: 'E-mail inválido')]
    #[Assert\Length(max: 180, maxMessage: 'E-mail muito longo')]
    private ?string $email = null;

    #[Assert\Length(
        min: 8,
        max: 4096,
        minMessage: 'Senha deve ter pelo menos 8 caracteres',
        maxMessage: 'Senha muito longa'
    )]
    private ?string $password = null;

    // Construtor

    public function __construct(?Usuario $usuario = null)
    {
        if ($usuario) {
            $this->id = $usuario->getId();
            $this->nome = $usuario->getNome();
            $this->email = $usuario->getEmail();
        }
    }

    // Getters

    public function getId(): ?int {
        return $this->id;
    }

    public function getNome(): ?string {
        return $this->nome;
    }
    
    public function getEmail(): ?string {
        return $this->email;
    }

    public function getPassword(): ?string {
        return $this->password;
    }

     // Setters

    public function setId(?int $id): void {
        $this->id = $id;
    }

    public function setNome(?string $nome): void {
        $this->nome = $nome;
    }

    public function setEmail(?string $email): void {
        $this->email = $email;
    }

    public function setPassword(?string $password): void {
        $this->password = $password;
    }
}
