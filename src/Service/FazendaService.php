<?php

namespace App\Service;

use App\Repository\FazendaRepository;
use App\Dto\FazendaDTO;
use App\Entity\Fazenda;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

class FazendaService {
    private FazendaRepository $fazendaRepository;
    private UsuarioRepository $usuarioRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(FazendaRepository $fazendaRepository, EntityManagerInterface $entityManager, UsuarioRepository $usuarioRepository)
    {
       $this->fazendaRepository = $fazendaRepository;
       $this->entityManager = $entityManager;
       $this->usuarioRepository = $usuarioRepository;
    }

    public function listarTodosPaginado(int $idUsuario, Request $request, PaginatorInterface $paginator) {
        $queryBuilder = $this->fazendaRepository->buscarPorUsuarioQuery($idUsuario, $request->query->get('search'));

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        $listFazendasDTO = [];
        foreach ($pagination->getItems() as $fazenda) {
            $listFazendasDTO[] = new FazendaDTO($fazenda);
        }

        $pagination->setItems($listFazendasDTO);

        return $pagination;
    }

    public function buscarPorId(int $idFazenda, int $idUsuario): ?FazendaDTO
    {
        $fazendaEntity = $this->fazendaRepository->find($idFazenda);

        if (!$fazendaEntity || !$this->fazendaPertenceAoUsuario($fazendaEntity, $idUsuario)) {
            return null;
        }

        return new FazendaDTO($fazendaEntity);
    }

    public function inserir(FazendaDTO $fazendaDTO, int $idUsuario): bool {
        $usuario = $this->usuarioRepository->find($idUsuario);

        if(!$usuario) {
            return false;
        }

        if ($this->fazendaRepository->existePorUsuarioENome($idUsuario, $fazendaDTO->getNome())) {
            return false;
        }

        $fazendaEntity = new Fazenda();

        if ($this->mapDtoParaEntity($fazendaDTO, $fazendaEntity)) {
            $fazendaEntity->setUsuario($usuario);
            $this->entityManager->persist($fazendaEntity);
            $this->entityManager->flush();
            return true;
        }

        return false;
        
    }

    public function alterar(FazendaDTO $fazendaDTO, int $idUsuario): bool {
        $fazendaEntity = $this->fazendaRepository->find($fazendaDTO->getId());

        if (!$fazendaEntity || !$this->fazendaPertenceAoUsuario($fazendaEntity, $idUsuario)) {
            return false;
        }

        if (
            $fazendaDTO->getNome() !== null &&
            strcasecmp($fazendaEntity->getNome() ?? '', $fazendaDTO->getNome()) !== 0 &&
            $this->fazendaRepository->existePorUsuarioENome($idUsuario, $fazendaDTO->getNome())
        ) {
            return false;
        }

        if ($this->mapDtoParaEntity($fazendaDTO, $fazendaEntity)) {
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    public function excluir(int $idFazenda, int $idUsuario): bool {
        $fazendaEntity = $this->fazendaRepository->find($idFazenda);

        if (!$fazendaEntity || !$this->fazendaPertenceAoUsuario($fazendaEntity, $idUsuario)) {
            return false;
        }

        $this->entityManager->remove($fazendaEntity);
        $this->entityManager->flush();

        return true;

    }

    public function contFazendas(int $idUsuario): int {
        return $this->fazendaRepository->contarPorUsuario($idUsuario);
    }

    public function listarUltimosCadastros(int $idUsuario): array {
        $fazendas = $this->fazendaRepository->buscarUltimasPorUsuario($idUsuario, 5);
        $listFazendasDTO = [];

        foreach ($fazendas as $fazenda) {
            $listFazendasDTO[] = new FazendaDTO($fazenda);
        }

        return $listFazendasDTO;
    }

    public function listarOpcoes(int $idUsuario): array
    {
        $fazendas = $this->fazendaRepository->buscarPorUsuario($idUsuario);
        $listFazendasDTO = [];

        foreach ($fazendas as $fazenda) {
            $listFazendasDTO[] = new FazendaDTO($fazenda);
        }

        return $listFazendasDTO;
    }

    private function mapDtoParaEntity(FazendaDTO $dto, Fazenda $entity): bool 
    {
        if (
            $dto->getNome() === null || 
            $dto->getResponsavel() === null || 
            $dto->getTamanhoHA() === null) {
            return false;
        }

        $entity->setNome($dto->getNome());
        $entity->setResponsavel($dto->getResponsavel());
        $entity->setTamanhoHA($dto->getTamanhoHA());

        return true;
    }

    private function fazendaPertenceAoUsuario(Fazenda $fazenda, int $idUsuario): bool
    {
        return $fazenda->getUsuario()?->getId() === $idUsuario;
    }
}
