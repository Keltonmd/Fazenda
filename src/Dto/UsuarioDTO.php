<?php

namespace App\Dto;

use App\Entity\Usuario;

class UsuarioDTO {
    private ?int $id = null;
    private ?string $nome = null;
    private ?string $email = null;
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
