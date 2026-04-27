<?php

namespace App\Service;

use App\Dto\UsuarioDTO;
use App\Entity\Usuario;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsuarioService {
    private UsuarioRepository $usuarioRepository;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UsuarioRepository $usuarioRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->usuarioRepository = $usuarioRepository;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function listarPorId(int $idUsuario): ?UsuarioDTO {
        $usuarioEntity = $this->usuarioRepository->find($idUsuario);

        if (!$usuarioEntity) {
            return null;
        }

        $usuarioDto = new UsuarioDTO($usuarioEntity);

        return $usuarioDto;
    }

    public function inserir(UsuarioDTO $usuarioDTO): bool {
        if ($usuarioDTO->getEmail() === null || $this->emailJaEstaEmUso($usuarioDTO->getEmail())) {
            return false;
        }

        $usuarioEntity = new Usuario();

        if ($this->mapDtoParaEntity($usuarioDTO, $usuarioEntity) && $usuarioDTO->getPassword() !== null) {

            $senhaHash = $this->passwordHasher->hashPassword(
                $usuarioEntity, 
                $usuarioDTO->getPassword()
            );

            $usuarioEntity->setPassword($senhaHash);

            $this->entityManager->persist($usuarioEntity);
            $this->entityManager->flush();
            return true;
        }

        return false;

    }

    public function alterarPassword(int $idUsuario, string $password): bool {
        $usuarioEntity = $this->usuarioRepository->find($idUsuario);

        if (!$usuarioEntity || !$password) {
            return false;
        }

        $senhaHash = $this->passwordHasher->hashPassword(
            $usuarioEntity,
            $password
        );

        $usuarioEntity->setPassword($senhaHash);

        $this->entityManager->flush();

        return true;

    }

    public function alterar(UsuarioDTO $usuarioDTO): bool {
        $usuarioEntity = $this->usuarioRepository->find($usuarioDTO->getId());

        if (!$usuarioEntity) {
            return false;
        }

        if ($usuarioDTO->getEmail() === null || $this->emailJaEstaEmUso($usuarioDTO->getEmail(), $usuarioEntity->getId())) {
            return false;
        }

        if ($this->mapDtoParaEntity($usuarioDTO, $usuarioEntity)) {
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    public function excluir(int $idUsuario): bool {
        $usuarioEntity = $this->usuarioRepository->find($idUsuario);

        if (!$usuarioEntity) {
            return false;
        }

        $this->entityManager->remove($usuarioEntity);
        $this->entityManager->flush();

        return true;
    }

    private function mapDtoParaEntity(UsuarioDTO $dto, Usuario $entity): bool {
        if ($dto->getNome() === null || $dto->getEmail() === null) {
            return false;
        }

        $entity->setNome($dto->getNome());
        $entity->setEmail($dto->getEmail());

        return true;
    }

    private function emailJaEstaEmUso(string $email, ?int $ignorarUsuarioId = null): bool
    {
        $usuario = $this->usuarioRepository->findOneBy(['email' => $email]);

        if (!$usuario) {
            return false;
        }

        return $usuario->getId() !== $ignorarUsuarioId;
    }
}
