<?php

namespace App\Service;

use App\Repository\GadoRepository;
use App\Dto\GadoDTO;
use App\Entity\Gado;
use App\Repository\FazendaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

class GadoService {
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
    public function listarTodosPorUsuarioPaginado(int $idUsuario, Request $request, PaginatorInterface $paginator){
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

    public function listarGadosAbatidosPaginado(int $idUsuario, Request $request, PaginatorInterface $paginator) {
        $queryBuilder = $this->gadoRepository->buscarAbatidosEPorUsuarioQuery($idUsuario, $request->query->get('search'),  $request->query->get('fazendaId'), $request->query->get('condicao'));

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

    public function listarGadosVivosPaginado(int $idUsuario, Request $request, PaginatorInterface $paginator){
        $queryBuilder = $this->gadoRepository->buscarVivosEPorUsuarioQuery($idUsuario,  $request->query->get('search'),  $request->query->get('fazendaId'));

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

    public function listarGadosParaAbatePaginado(int $idUsuario, Request $request, PaginatorInterface $paginator){
        $queryBuilder = $this->gadoRepository->buscarParaAbateQuery($idUsuario, $request->query->get('search'),  $request->query->get('fazendaId'), $request->query->get('condicao'));

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

    public function mandarParaAbate(array $idsGado, int $idUsuario): bool {
        $quantidade = $this->gadoRepository->mandarParaAbate($idsGado, $idUsuario);

        if($quantidade == 0) {
            return false;
        }

        return true;
    }

    public function cancelarAbate(int $idGado, int $idUsuario, ?int $novoCodigo = null): bool
    {
        $gado = $this->gadoRepository->find($idGado);

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

    public function inserir(GadoDTO $gadoDTO, int $idFazenda, int $idUsuario): bool {
        $fazenda = $this->fazendaRepository->find($idFazenda);

        if (!$fazenda || $fazenda->getUsuario()?->getId() !== $idUsuario) {
            return false;
        }

        $quantidadeGados = $this->gadoRepository->contarPorFazenda($idFazenda);
        $tamanho = $fazenda->getTamanhoHA() * 18;

        if ($quantidadeGados >= $tamanho) {
            return false;
        }

        if ($this->existeGadoComCodigo($idUsuario, $gadoDTO->getCodigo())) {
            return false;
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

    public function alterar(GadoDTO $gadoDTO, int $idUsuario): bool {
        $gadoEntity = $this->gadoRepository->find($gadoDTO->getId());

        if (!$gadoEntity || $gadoEntity->getFazenda()?->getUsuario()?->getId() !== $idUsuario) {
            return false;
        }

        if ($this->mapDtoParaEntity($gadoDTO, $gadoEntity)) {
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    public function excluir(int $idGado, int $idUsuario): bool {
        $gadoEntity = $this->gadoRepository->find($idGado);

        if (!$gadoEntity || $gadoEntity->getFazenda()?->getUsuario()?->getId() !== $idUsuario) {
            return false;
        }

        $this->entityManager->remove($gadoEntity);
        $this->entityManager->flush();

        return true;

    }

    public function calcularLeiteSemanalPorUsuario(int $idUsuario): float {
        $gadosEntity = $this->gadoRepository->buscarNaoAbatidosEPorUsuario($idUsuario);

        $leiteSemanal = 0;

        foreach($gadosEntity as $gado) {
            $leiteSemanal += $gado->getLeite();
        }

        return $leiteSemanal;

    }

    public function calcularRacaoSemanalPorUsuario(int $idUsuario): float {
        $gadosEntity = $this->gadoRepository->buscarNaoAbatidosEPorUsuario($idUsuario);

        $racaoSemanal = 0;

        foreach($gadosEntity as $gado) {
            $racaoSemanal += $gado->getRacao();
        }

        return $racaoSemanal;
    }

    public function contarAnimaisElegiveis(int $idUsuario): int {
        $gadosEntity = $this->gadoRepository->buscarNaoAbatidosEPorUsuario($idUsuario);

        $cont = 0;

        foreach ($gadosEntity as $gado) {
            $idade = $this->calcularIdadeAnos($gado->getNascimento());
            $racaoSemanal = $gado->getRacao();

            if($racaoSemanal > 500 && $idade <= 1) {
                $cont +=1;
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

    public function listarUltimosCadastros(int $idUsuario): array {
        $gados = $this->gadoRepository->buscarUltimosPorUsuario($idUsuario, 5);
        $listGadoDTO = [];

        foreach ($gados as $gado) {
            $listGadoDTO[] = new GadoDTO($gado);
        }

        return $listGadoDTO;
    }

    public function existeGadoComCodigo(int $usuarioId, int $codigo): bool {
        return $this->gadoRepository->existeGadoVivoPorCodigo($codigo, $usuarioId);
    }

    private function mapDtoParaEntity(GadoDTO $dto, Gado $entity): bool {
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

    private function calcularIdadeAnos(\DateTimeImmutable $nascimento): int {
        $hoje = new \DateTimeImmutable();
        $intervalo = $nascimento->diff($hoje);

        return $intervalo->y;
    }

    /** Obtenha o peso vivo: Pesagem do animal (em kg). Calcule a carcaça: Multiplique o peso vivo pelo rendimento (ex: 50% = 0,50). Divida por 15: O resultado em kg dividido por 15 dá o número de arrobas. */
    private function calcularArroba(float $peso): float {
        return ($peso * 0.5) / 15;
    }

    //quantidade ingerida por semana dividido por 7
    private function calcularRacaoIngerida(float $racao): float {
        return $racao / 7;
    }

    private function deveIrParaAbate(Gado $gado): bool{
        $idade = $this->calcularIdadeAnos($gado->getNascimento());
        $litrosLeite = $gado->getLeite();
        $arroba = $this->calcularArroba($gado->getPeso());
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

        if ($arroba >= 18) {
            return true;
        }
        
        return false;
    }
}
