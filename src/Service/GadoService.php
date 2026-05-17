<?php

namespace App\Service;

use App\Repository\GadoRepository;
use App\Dto\GadoDTO;
use App\Entity\Gado;
use App\Repository\FazendaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

class GadoService
{
    private GadoRepository $gadoRepository;
    private FazendaRepository $fazendaRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(GadoRepository $gadoRepository, EntityManagerInterface $entityManager, FazendaRepository $fazendaRepository)
    {
        $this->gadoRepository = $gadoRepository;
        $this->entityManager = $entityManager;
        $this->fazendaRepository = $fazendaRepository;
    }

    ## Paginacao
    public function listarTodosPorUsuarioPaginado(int $idUsuario, Request $request, PaginatorInterface $paginator)
    {
        $queryBuilder = $this->gadoRepository->buscarPorUsuarioQuery($idUsuario);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        $listGadoDTO = [];

        foreach ($pagination->getItems() as $gado) {
            $listGadoDTO[] = new GadoDTO($gado);
        }

        $pagination->setItems($listGadoDTO);

        return $pagination;
    }

    public function listarGadosAbatidosPaginado(int $idUsuario, Request $request, PaginatorInterface $paginator)
    {
        $queryBuilder = $this->gadoRepository->buscarAbatidosEPorUsuarioQuery($idUsuario, $request->query->get('search'), $request->query->get('fazendaId'), $request->query->get('condicao'));

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        $listGadoDTO = [];

        foreach ($pagination->getItems() as $gado) {
            $listGadoDTO[] = new GadoDTO($gado);
        }

        $pagination->setItems($listGadoDTO);

        return $pagination;
    }

    public function listarGadosVivosPaginado(int $idUsuario, Request $request, PaginatorInterface $paginator)
    {
        $queryBuilder = $this->gadoRepository->buscarVivosEPorUsuarioQuery($idUsuario, $request->query->get('search'), $request->query->get('fazendaId'));

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        $listGadoDTO = [];

        foreach ($pagination->getItems() as $gado) {
            $listGadoDTO[] = new GadoDTO($gado);
        }

        $pagination->setItems($listGadoDTO);

        return $pagination;
    }

    public function listarGadosParaAbatePaginado(int $idUsuario, Request $request, PaginatorInterface $paginator)
    {
        $queryBuilder = $this->gadoRepository->buscarParaAbateQuery($idUsuario, $request->query->get('search'), $request->query->get('fazendaId'), $request->query->get('condicao'));

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        $listGadoDTO = [];

        foreach ($pagination->getItems() as $gado) {
            $listGadoDTO[] = new GadoDTO($gado);
        }

        $pagination->setItems($listGadoDTO);

        return $pagination;
    }

    ## regras

    public function mandarParaAbate(array $idsGado, int $idUsuario): bool
    {
        $idsGado = array_values(array_unique($idsGado));

        if ($idsGado === []) {
            return false;
        }

        $gados = $this->gadoRepository->buscarVivosPorIdsEUsuario($idsGado, $idUsuario);

        if (count($gados) !== count($idsGado)) {
            throw new \DomainException('Um ou mais animais selecionados são inválidos ou não estão disponíveis para abate.');
        }

        foreach ($gados as $gado) {
            if (!$this->deveIrParaAbate($gado)) {
                throw new \DomainException(sprintf(
                    'O gado de código %d não está apto para abate.',
                    $gado->getCodigo()
                ));
            }
        }

        $quantidade = $this->gadoRepository->mandarParaAbate($idsGado, $idUsuario);

        if ($quantidade == 0) {
            return false;
        }

        return true;
    }

    public function cancelarAbate(Gado $gado, int $idUsuario, ?int $novoCodigo = null): bool
    {
        if (!$gado || $gado->getFazenda()?->getUsuario()?->getId() !== $idUsuario) {
            return false;
        }

        if (!$gado->isAbatido()) {
            return false;
        }

        $dataAbate = $gado->getDataAbate();

        if ($dataAbate === null) {
            throw new \Exception('Este abate não pode ser cancelado porque não possui data de abate registrada.');
        }

        if (new \DateTimeImmutable() > $dataAbate->modify('+1 day')) {
            throw new \Exception('O prazo de 1 dia para cancelar o abate já expirou.');
        }

        $codigoAtual = $gado->getCodigo();

        $existeCodigo = $this->gadoRepository
            ->existeGadoVivoPorCodigo($codigoAtual, $idUsuario);

        if ($existeCodigo) {

            if ($novoCodigo === null) {
                throw new \Exception('Código já está em uso. Informe um novo código.');
            }

            if ($this->gadoRepository->existeGadoVivoPorCodigo($novoCodigo, $idUsuario)) {
                throw new \Exception('Novo código também já está em uso.');
            }

            $gado->setCodigo($novoCodigo);
        }

        $gado->setAbatido(false);
        $gado->setDataAbate(null);

        $this->entityManager->flush();

        return true;
    }

    public function inserir(GadoDTO $gadoDTO, int $idUsuario): bool
    {
        $idFazenda = $gadoDTO->getFazendaId();

        if ($idFazenda === null) {
            throw new \DomainException('Fazenda inválida.');
        }

        $fazenda = $this->fazendaRepository->find($idFazenda);

        if (!$fazenda || $fazenda->getUsuario()?->getId() !== $idUsuario) {
            throw new \DomainException('Fazenda inválida.');
        }

        $quantidadeGados = $this->gadoRepository->contarPorFazenda($idFazenda);
        $tamanho = $fazenda->getTamanhoHA() * 18;

        if ($quantidadeGados >= $tamanho) {
            throw new \DomainException('A fazenda selecionada atingiu o limite de animais para a área cadastrada.');
        }

        if ($this->existeGadoComCodigo($idUsuario, $gadoDTO->getCodigo())) {
            throw new \DomainException('Já existe um gado vivo com esse código.');
        }

        $gadoEntity = new Gado();

        if ($this->mapDtoParaEntity($gadoDTO, $gadoEntity)) {
            $gadoEntity->setFazenda($fazenda);
            $this->entityManager->persist($gadoEntity);
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    public function alterar(GadoDTO $gadoDTO, int $idUsuario): bool
    {
        $gadoEntity = $this->gadoRepository->find($gadoDTO->getId());

        if (!$gadoEntity || $gadoEntity->getFazenda()?->getUsuario()?->getId() !== $idUsuario) {
            return false;
        }

        if (
            $gadoDTO->getCodigo() !== null &&
            $gadoDTO->getCodigo() !== $gadoEntity->getCodigo() &&
            $this->gadoRepository->existeGadoVivoPorCodigoExcetoId(
                $gadoDTO->getCodigo(),
                $idUsuario,
                $gadoEntity->getId()
            )
        ) {
            throw new \DomainException('Já existe um gado vivo com esse código.');
        }

        if ($gadoDTO->getFazendaId() !== null && $gadoDTO->getFazendaId() !== $gadoEntity->getFazenda()?->getId()) {
            $novaFazenda = $this->fazendaRepository->find($gadoDTO->getFazendaId());

            if (!$novaFazenda || $novaFazenda->getUsuario()?->getId() !== $idUsuario) {
                throw new \DomainException('Fazenda inválida.');
            }

            $quantidadeGados = $this->gadoRepository->contarPorFazenda($novaFazenda->getId());
            $tamanho = $novaFazenda->getTamanhoHA() * 18;

            if ($quantidadeGados >= $tamanho) {
                throw new \DomainException('A fazenda selecionada atingiu o limite de animais para a área cadastrada.');
            }

            $gadoEntity->setFazenda($novaFazenda);
        }

        if ($this->mapDtoParaEntity($gadoDTO, $gadoEntity)) {
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    public function excluir(Gado $gadoEntity, int $idUsuario): bool
    {
        if (!$gadoEntity || $gadoEntity->getFazenda()?->getUsuario()?->getId() !== $idUsuario) {
            return false;
        }

        $this->entityManager->remove($gadoEntity);
        $this->entityManager->flush();

        return true;

    }

    public function calcularLeiteSemanalPorUsuario(int $idUsuario): float
    {
        $gadosEntity = $this->gadoRepository->buscarNaoAbatidosEPorUsuario($idUsuario);

        $leiteSemanal = 0;

        foreach ($gadosEntity as $gado) {
            $leiteSemanal += $gado->getLeite();
        }

        return $leiteSemanal;

    }

    public function calcularRacaoSemanalPorUsuario(int $idUsuario): float
    {
        $gadosEntity = $this->gadoRepository->buscarNaoAbatidosEPorUsuario($idUsuario);

        $racaoSemanal = 0;

        foreach ($gadosEntity as $gado) {
            $racaoSemanal += $gado->getRacao();
        }

        return $racaoSemanal;
    }

    public function contarAnimaisElegiveis(int $idUsuario): int
    {
        $gadosEntity = $this->gadoRepository->buscarNaoAbatidosEPorUsuario($idUsuario);

        $cont = 0;

        foreach ($gadosEntity as $gado) {
            $idade = $this->calcularIdadeAnos($gado->getNascimento());
            $racaoSemanal = $gado->getRacao();

            if ($racaoSemanal > 500 && $idade <= 1) {
                $cont += 1;
            }
        }

        return $cont;
    }

    public function contGados(int $idUsuario): array
    {
        return [
            'vivos' => $this->gadoRepository->contarPorUsuarioEStatus($idUsuario, false),
            'abatidos' => $this->gadoRepository->contarPorUsuarioEStatus($idUsuario, true),
        ];
    }

    public function resumoAbates(int $idUsuario): array
    {
        return [
            'quantidadeParaAbate' => $this->gadoRepository->contarParaAbatePorUsuario($idUsuario),
            'quantidadeAbatidos' => $this->gadoRepository->contarPorUsuarioEStatus($idUsuario, true),
            'pesoMedioAbatidos' => $this->gadoRepository->calcularPesoMedioAbatidosPorUsuario($idUsuario),
            'leitePerdidoAbatidos' => $this->gadoRepository->calcularLeitePerdidoAbatidosPorUsuario($idUsuario),
        ];
    }

    public function listarUltimosCadastros(int $idUsuario): array
    {
        $gados = $this->gadoRepository->buscarUltimosPorUsuario($idUsuario, 5);
        $listGadoDTO = [];

        foreach ($gados as $gado) {
            $listGadoDTO[] = new GadoDTO($gado);
        }

        return $listGadoDTO;
    }

    public function existeGadoComCodigo(int $usuarioId, int $codigo): bool
    {
        return $this->gadoRepository->existeGadoVivoPorCodigo($codigo, $usuarioId);
    }

    private function mapDtoParaEntity(GadoDTO $dto, Gado $entity): bool
    {
        if ($dto->getCodigo() === null || $dto->getLeite() === null || $dto->getPeso() === null || $dto->getNascimento() === null || $dto->getRacao() === null) {
            return false;
        }

        $entity->setCodigo($dto->getCodigo());
        $entity->setLeite($dto->getLeite());
        $entity->setRacao($dto->getRacao());
        $entity->setPeso($dto->getPeso());
        $entity->setNascimento($dto->getNascimento());

        return true;
    }

    private function calcularIdadeAnos(\DateTimeImmutable $nascimento): int
    {
        $hoje = new \DateTimeImmutable();
        $intervalo = $nascimento->diff($hoje);

        return $intervalo->y;
    }

    private function calcularArrobas(float $peso): float
    {
        return $peso / 15;
    }

    //quantidade ingerida por semana dividido por 7
    private function calcularRacaoIngerida(float $racao): float
    {
        return $racao / 7;
    }

    private function deveIrParaAbate(Gado $gado): bool
    {
        $idade = $this->calcularIdadeAnos($gado->getNascimento());
        $litrosLeite = $gado->getLeite();
        $arrobas = $this->calcularArrobas($gado->getPeso());
        $racaoIngerida = $this->calcularRacaoIngerida($gado->getRacao());

        if ($idade > 5) {
            return true;
        }

        if ($litrosLeite < 40) {
            return true;
        }

        if ($racaoIngerida > 50 && $litrosLeite < 70) {
            return true;
        }

        if ($arrobas > 18) {
            return true;
        }

        return false;
    }
}
