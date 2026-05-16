<?php

namespace App\Service;

use App\Repository\FazendaRepository;
use App\Dto\FazendaDTO;
use App\Entity\Fazenda;
use App\Repository\UsuarioRepository;
use App\Repository\VeterinarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

class FazendaService {
    private FazendaRepository $fazendaRepository;
    private UsuarioRepository $usuarioRepository;
    private VeterinarioRepository $veterinarioRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(FazendaRepository $fazendaRepository, EntityManagerInterface $entityManager, UsuarioRepository $usuarioRepository, VeterinarioRepository $veterinarioRepository)
    {
       $this->fazendaRepository = $fazendaRepository;
       $this->entityManager = $entityManager;
       $this->usuarioRepository = $usuarioRepository;
       $this->veterinarioRepository = $veterinarioRepository;
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

    public function buscarPorId(Fazenda $fazendaEntity, int $idUsuario): ?FazendaDTO
    {
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
            throw new \DomainException('Já existe uma fazenda com esse nome.');
        }

        $fazendaEntity = new Fazenda();

        if ($this->mapDtoParaEntity($fazendaDTO, $fazendaEntity)) {
            $fazendaEntity->setUsuario($usuario);
            if (!$this->sincronizarVeterinarios($fazendaEntity, $idUsuario, $fazendaDTO->getVeterinariosIds())) {
                throw new \DomainException('Um ou mais veterinários selecionados são inválidos.');
            }
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
            throw new \DomainException('Já existe uma fazenda com esse nome.');
        }

        if ($this->mapDtoParaEntity($fazendaDTO, $fazendaEntity)) {
            if (!$this->sincronizarVeterinarios($fazendaEntity, $idUsuario, $fazendaDTO->getVeterinariosIds())) {
                throw new \DomainException('Um ou mais veterinários selecionados são inválidos.');
            }
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    public function excluir(Fazenda $fazendaEntity, int $idUsuario): bool {
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

    /** @return Fazenda[] */
    public function listarEntidades(int $idUsuario): array
    {
        return $this->fazendaRepository->buscarPorUsuario($idUsuario);
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

    private function sincronizarVeterinarios(Fazenda $fazenda, int $idUsuario, array $veterinariosIds): bool
    {
        $ids = array_values(array_unique(array_filter(
            $veterinariosIds,
            static fn (mixed $id): bool => is_int($id) && $id > 0
        )));

        $veterinarios = $ids === [] ? [] : $this->veterinarioRepository->findBy(['id' => $ids]);

        if (count($veterinarios) !== count($ids)) {
            return false;
        }

        foreach ($veterinarios as $veterinario) {
            if ($veterinario->getUsuario()?->getId() !== $idUsuario) {
                return false;
            }
        }

        foreach ($fazenda->getVeterinarios()->toArray() as $veterinarioAtual) {
            $fazenda->removeVeterinario($veterinarioAtual);
        }

        foreach ($veterinarios as $veterinario) {
            $fazenda->addVeterinario($veterinario);
        }

        return true;
    }

    private function fazendaPertenceAoUsuario(Fazenda $fazenda, int $idUsuario): bool
    {
        return $fazenda->getUsuario()?->getId() === $idUsuario;
    }
}
