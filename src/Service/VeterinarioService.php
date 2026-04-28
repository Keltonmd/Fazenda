<?php

namespace App\Service;

use App\Dto\VeterinarioDTO;
use App\Entity\Veterinario;
use App\Repository\FazendaRepository;
use App\Repository\UsuarioRepository;
use App\Repository\VeterinarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

class VeterinarioService {
    private VeterinarioRepository $veterinarioRepository;
    private UsuarioRepository $usuarioRepository;
    private FazendaRepository $fazendaRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(VeterinarioRepository $veterinarioRepository, EntityManagerInterface $entityManager, UsuarioRepository $usuarioRepository, FazendaRepository $fazendaRepository)
    {
        $this->veterinarioRepository = $veterinarioRepository;
        $this->entityManager = $entityManager;
        $this->usuarioRepository = $usuarioRepository;
        $this->fazendaRepository = $fazendaRepository;
    }

    public function listarTodosVeterinariosPaginado(int $idUsuario, Request $request, PaginatorInterface $paginator) {
        $queryBuilder = $this->veterinarioRepository->buscarPorUsuarioQuery($idUsuario, $request->query->get('search'));

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        $listVeterinarioDTO = [];
        
        foreach ($pagination->getItems() as $veterinario) {
            $listVeterinarioDTO[] = new VeterinarioDTO($veterinario);
        }

        $pagination->setItems($listVeterinarioDTO);

        return $pagination;
    }

    public function inserir(VeterinarioDTO $veterinarioDTO, int $idUsuario, ?int $idFazenda): bool {
        $usuario = $this->usuarioRepository->find($idUsuario);
        $fazenda = $idFazenda !== null ? $this->fazendaRepository->find($idFazenda) : null;

        if(!$usuario) {
            return false;
        }

        if ($idFazenda !== null && !$fazenda) {
            return false;
        }

        if ($fazenda && $fazenda->getUsuario()?->getId() !== $idUsuario) {
            return false;
        }

        if ($this->veterinarioRepository->existePorUsuarioECrmv($idUsuario, $veterinarioDTO->getCrmv())) {
            return false;
        }

        $veterinarioEntity = new Veterinario();

        if ($this->mapDtoParaEntity($veterinarioDTO, $veterinarioEntity)) {
            $veterinarioEntity->setUsuario($usuario);
            if ($fazenda) {
                $veterinarioEntity->addFazenda($fazenda);
            }
            $this->entityManager->persist($veterinarioEntity);
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    public function alterar(VeterinarioDTO $veterinarioDTO, int $idUsuario): bool {
        $veterinarioEntity = $this->veterinarioRepository->find($veterinarioDTO->getId());

        if (!$veterinarioEntity || $veterinarioEntity->getUsuario()?->getId() !== $idUsuario) {
            return false;
        }

        if (
            $veterinarioDTO->getCrmv() !== null &&
            strcasecmp($veterinarioEntity->getCrmv() ?? '', $veterinarioDTO->getCrmv()) !== 0 &&
            $this->veterinarioRepository->existePorUsuarioECrmv($idUsuario, $veterinarioDTO->getCrmv())
        ) {
            return false;
        }

        if ($this->mapDtoParaEntity($veterinarioDTO, $veterinarioEntity)) {
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    public function excluir(int $idVeterinario, int $idUsuario): bool {
        $veterinarioEntity = $this->veterinarioRepository->find($idVeterinario);

        if (!$veterinarioEntity || $veterinarioEntity->getUsuario()?->getId() !== $idUsuario) {
            return false;
        }

        $this->entityManager->remove($veterinarioEntity);
        $this->entityManager->flush();

        return true;
    }

    public function adicionarFazenda(int $idVeterinario, int $idFazenda, int $idUsuario): bool
    {
        $veterinario = $this->veterinarioRepository->find($idVeterinario);
        $fazenda = $this->fazendaRepository->find($idFazenda);

        if (!$veterinario || !$fazenda) {
            return false;
        }

        if ($veterinario->getUsuario()?->getId() !== $idUsuario) {
            return false;
        }

        if ($fazenda->getUsuario()?->getId() !== $idUsuario) {
            return false;
        }

        if (!$veterinario->getFazendas()->contains($fazenda)) {
            $veterinario->addFazenda($fazenda);
        }
        $this->entityManager->flush();

        return true;
    }

    public function removerFazenda(int $idVeterinario, int $idFazenda, int $idUsuario): bool
    {
        $veterinario = $this->veterinarioRepository->find($idVeterinario);
        $fazenda = $this->fazendaRepository->find($idFazenda);
        
        if (!$veterinario || !$fazenda) {
            return false;
        }

        if ($veterinario->getUsuario()?->getId() !== $idUsuario) {
            return false;
        }

        if ($fazenda->getUsuario()?->getId() !== $idUsuario) {
            return false;
        }

        if ($veterinario->getFazendas()->contains($fazenda)) {
            $veterinario->removeFazenda($fazenda);
        }

        $this->entityManager->flush();

        return true;
    }

    public function contVeterinarios(int $idUsuario): int
    {
        return $this->veterinarioRepository->contarPorUsuario($idUsuario);
    }

    public function listarUltimosCadastros(int $idUsuario): array
    {
        $veterinarios = $this->veterinarioRepository->buscarUltimosPorUsuario($idUsuario, 5);
        $listVeterinarioDTO = [];

        foreach ($veterinarios as $veterinario) {
            $listVeterinarioDTO[] = new VeterinarioDTO($veterinario);
        }

        return $listVeterinarioDTO;
    }

    private function mapDtoParaEntity(VeterinarioDTO $dto, Veterinario $entity): bool {
        if ($dto->getNome() === null || $dto->getCrmv() === null) {
            return false;
        }

        $entity->setNome($dto->getNome());
        $entity->setCrmv($dto->getCrmv());

        return true;
    }
}
