<?php

namespace App\Service;

use App\Dto\VeterinarioDTO;
use App\Entity\Fazenda;
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

    public function inserir(VeterinarioDTO $veterinarioDTO, int $idUsuario): bool {
        $usuario = $this->usuarioRepository->find($idUsuario);

        if(!$usuario) {
            return false;
        }

        if ($this->veterinarioRepository->existePorUsuarioECrmv($idUsuario, $veterinarioDTO->getCrmv())) {
            throw new \DomainException('Já existe um veterinário com esse CRMV.');
        }

        $veterinarioEntity = new Veterinario();

        if ($this->mapDtoParaEntity($veterinarioDTO, $veterinarioEntity)) {
            $veterinarioEntity->setUsuario($usuario);
            if (!$this->sincronizarFazendas($veterinarioEntity, $idUsuario, $veterinarioDTO->getFazendasIds())) {
                throw new \DomainException('Uma ou mais fazendas selecionadas são inválidas.');
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
            throw new \DomainException('Já existe um veterinário com esse CRMV.');
        }

        if ($this->mapDtoParaEntity($veterinarioDTO, $veterinarioEntity)) {
            if (!$this->sincronizarFazendas($veterinarioEntity, $idUsuario, $veterinarioDTO->getFazendasIds())) {
                throw new \DomainException('Uma ou mais fazendas selecionadas são inválidas.');
            }
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    public function excluir(Veterinario $veterinarioEntity, int $idUsuario): bool {
        if (!$veterinarioEntity || $veterinarioEntity->getUsuario()?->getId() !== $idUsuario) {
            return false;
        }

        $this->entityManager->remove($veterinarioEntity);
        $this->entityManager->flush();

        return true;
    }

    public function adicionarFazenda(Veterinario $veterinario, Fazenda $fazenda, int $idUsuario): bool
    {
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

    public function removerFazenda(Veterinario $veterinario, Fazenda $fazenda, int $idUsuario): bool
    {
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

    /** @return Veterinario[] */
    public function listarEntidades(int $idUsuario): array
    {
        return $this->veterinarioRepository->buscarPorUsuario($idUsuario);
    }

    private function mapDtoParaEntity(VeterinarioDTO $dto, Veterinario $entity): bool {
        if ($dto->getNome() === null || $dto->getCrmv() === null) {
            return false;
        }

        $entity->setNome($dto->getNome());
        $entity->setCrmv($dto->getCrmv());

        return true;
    }

    private function sincronizarFazendas(Veterinario $veterinario, int $idUsuario, array $fazendasIds): bool
    {
        $ids = array_values(array_unique(array_filter(
            $fazendasIds,
            static fn (mixed $id): bool => is_int($id) && $id > 0
        )));

        $fazendas = $ids === [] ? [] : $this->fazendaRepository->findBy(['id' => $ids]);

        if (count($fazendas) !== count($ids)) {
            return false;
        }

        foreach ($fazendas as $fazenda) {
            if ($fazenda->getUsuario()?->getId() !== $idUsuario) {
                return false;
            }
        }

        foreach ($veterinario->getFazendas()->toArray() as $fazendaAtual) {
            $veterinario->removeFazenda($fazendaAtual);
        }

        foreach ($fazendas as $fazenda) {
            $veterinario->addFazenda($fazenda);
        }

        return true;
    }
}
