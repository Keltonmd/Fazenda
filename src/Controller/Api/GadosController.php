<?php

namespace App\Controller\Api;

use App\Dto\GadoDTO;
use App\Entity\Gado;
use App\Form\GadoType;
use App\Service\FazendaService;
use App\Service\GadoService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class GadosController extends AbstractController
{
    use HandlesJsonFormRequestsTrait;

    private GadoService $gadoService;
    private FazendaService $fazendaService;

    public function __construct(GadoService $gadoService, FazendaService $fazendaService)
    {
        $this->gadoService = $gadoService;
        $this->fazendaService = $fazendaService;
    }

    #[Route('/api/gados', methods: ['GET'])]
    public function listar(Request $request, PaginatorInterface $paginator): Response
    {
        $usuario = $this->getAuthenticatedUsuario();

        $pagination = $this->gadoService->listarTodosPorUsuarioPaginado($usuario->getId(), $request, $paginator);

        $dados = [];

        foreach ($pagination as $gado) {
            $dados[] = [
                'id' => $gado->getId(),
                'fazendaId' => $gado->getFazendaId(),
                'codigo' => $gado->getCodigo(),
                'leite' => $gado->getLeite(),
                'racao' => $gado->getRacao(),
                'peso' => $gado->getPeso(),
                'nascimento' => $gado->getNascimento(),
                'abatido' => $gado->isAbatido(),
                'dataAbate' => $gado->getDataAbate(),
                'podeCancelarAbate' => $gado->podeCancelarAbate(),
                'dataLimiteCancelamentoAbate' => $gado->getDataLimiteCancelamentoAbate(),
            ];
        }

        return $this->json([
            'data' => $dados,
            'pagination' => [
                'currentPage' => $pagination->getCurrentPageNumber(),
                'totalPages' => max(
                    1,
                    ceil($pagination->getTotalItemCount() / $pagination->getItemNumberPerPage())
                ),
                'totalItems' => $pagination->getTotalItemCount(),
                'itemsPerPage' => $pagination->getItemNumberPerPage(),
            ]
        ]);
    }

    #[Route('/api/gados/abate', methods: ['GET'])]
    public function listarParaAbate(Request $request, PaginatorInterface $paginator): Response
    {
        $usuario = $this->getAuthenticatedUsuario();

        $pagination = $this->gadoService->listarGadosParaAbatePaginado($usuario->getId(), $request, $paginator);

        $dados = [];

        foreach ($pagination as $gado) {
            $dados[] = [
                'id' => $gado->getId(),
                'codigo' => $gado->getCodigo(),
                'leite' => $gado->getLeite(),
                'racao' => $gado->getRacao(),
                'peso' => $gado->getPeso(),
                'nascimento' => $gado->getNascimento(),
                'abatido' => $gado->isAbatido(),
                'fazendaId' => $gado->getFazendaId(),
                'dataAbate' => $gado->getDataAbate(),
                'podeCancelarAbate' => $gado->podeCancelarAbate(),
                'dataLimiteCancelamentoAbate' => $gado->getDataLimiteCancelamentoAbate(),
            ];
        }

        return $this->json([
            'data' => $dados,
            'pagination' => [
                'currentPage' => $pagination->getCurrentPageNumber(),
                'totalPages' => max(
                    1,
                    ceil($pagination->getTotalItemCount() / $pagination->getItemNumberPerPage())
                ),
                'totalItems' => $pagination->getTotalItemCount(),
                'itemsPerPage' => $pagination->getItemNumberPerPage(),
            ]
        ]);
    }

    #[Route('/api/gados/abatidos', methods: ['GET'])]
    public function listarAbatidos(Request $request, PaginatorInterface $paginator): Response
    {
        $usuario = $this->getAuthenticatedUsuario();

        $pagination = $this->gadoService->listarGadosAbatidosPaginado($usuario->getId(), $request, $paginator);

        $dados = [];

        foreach ($pagination as $gado) {
            $dados[] = [
                'id' => $gado->getId(),
                'codigo' => $gado->getCodigo(),
                'leite' => $gado->getLeite(),
                'racao' => $gado->getRacao(),
                'peso' => $gado->getPeso(),
                'nascimento' => $gado->getNascimento(),
                'abatido' => $gado->isAbatido(),
                'fazendaId' => $gado->getFazendaId(),
                'dataAbate' => $gado->getDataAbate(),
                'podeCancelarAbate' => $gado->podeCancelarAbate(),
                'dataLimiteCancelamentoAbate' => $gado->getDataLimiteCancelamentoAbate(),
            ];
        }

        return $this->json([
            'data' => $dados,
            'pagination' => [
                'currentPage' => $pagination->getCurrentPageNumber(),
                'totalPages' => max(
                    1,
                    ceil($pagination->getTotalItemCount() / $pagination->getItemNumberPerPage())
                ),
                'totalItems' => $pagination->getTotalItemCount(),
                'itemsPerPage' => $pagination->getItemNumberPerPage(),
            ]
        ]);
    }

    #[Route('/api/gados/vivos', methods: ['GET'])]
    public function listarVivos(Request $request, PaginatorInterface $paginator): Response
    {
        $usuario = $this->getAuthenticatedUsuario();

        $pagination = $this->gadoService->listarGadosVivosPaginado($usuario->getId(), $request, $paginator);

        $dados = [];

        foreach ($pagination as $gado) {
            $dados[] = [
                'id' => $gado->getId(),
                'fazendaId' => $gado->getFazendaId(),
                'codigo' => $gado->getCodigo(),
                'leite' => $gado->getLeite(),
                'racao' => $gado->getRacao(),
                'peso' => $gado->getPeso(),
                'nascimento' => $gado->getNascimento(),
                'abatido' => $gado->isAbatido(),
                'dataAbate' => $gado->getDataAbate(),
                'podeCancelarAbate' => $gado->podeCancelarAbate(),
                'dataLimiteCancelamentoAbate' => $gado->getDataLimiteCancelamentoAbate(),
            ];
        }

        return $this->json([
            'data' => $dados,
            'pagination' => [
                'currentPage' => $pagination->getCurrentPageNumber(),
                'totalPages' => max(
                    1,
                    ceil($pagination->getTotalItemCount() / $pagination->getItemNumberPerPage())
                ),
                'totalItems' => $pagination->getTotalItemCount(),
                'itemsPerPage' => $pagination->getItemNumberPerPage(),
            ]
        ]);
    }

    #[Route('/api/gados/resumo', methods: ['GET'])]
    public function resumo(): Response
    {
        $usuario = $this->getAuthenticatedUsuario();

        $contagem = $this->gadoService->contGados($usuario->getId());

        return $this->json([
            'leiteSemanal' => $this->gadoService->calcularLeiteSemanalPorUsuario($usuario->getId()),
            'racaoSemanal' => $this->gadoService->calcularRacaoSemanalPorUsuario($usuario->getId()),
            'animaisElegiveis' => $this->gadoService->contarAnimaisElegiveis($usuario->getId()),
            'gadosVivos' => $contagem['vivos'],
            'gadosAbatidos' => $contagem['abatidos'],
        ]);
    }

    #[Route('/api/gados/abates/resumo', methods: ['GET'])]
    public function resumoAbates(): Response
    {
        $usuario = $this->getAuthenticatedUsuario();

        return $this->json($this->gadoService->resumoAbates($usuario->getId()));
    }

    #[Route('/api/gados/ultimos-cadastros', methods: ['GET'])]
    public function ultimosCadastros(): Response
    {
        $usuario = $this->getAuthenticatedUsuario();

        $dados = [];

        foreach ($this->gadoService->listarUltimosCadastros($usuario->getId()) as $gado) {
            $dados[] = [
                'id' => $gado->getId(),
                'fazendaId' => $gado->getFazendaId(),
                'codigo' => $gado->getCodigo(),
                'leite' => $gado->getLeite(),
                'racao' => $gado->getRacao(),
                'peso' => $gado->getPeso(),
                'nascimento' => $gado->getNascimento(),
                'abatido' => $gado->isAbatido(),
                'dataAbate' => $gado->getDataAbate(),
                'podeCancelarAbate' => $gado->podeCancelarAbate(),
                'dataLimiteCancelamentoAbate' => $gado->getDataLimiteCancelamentoAbate(),
            ];
        }

        return $this->json([
            'data' => $dados,
        ]);
    }

    #[Route('/api/gados/codigo-existe/{codigo}', methods: ['GET'])]
    public function verificarCodigo(int $codigo): Response
    {
        $usuario = $this->getAuthenticatedUsuario();

        if ($codigo <= 0) {
            return $this->json(['error' => 'Código inválido'], 400);
        }

        $existe = $this->gadoService->existeGadoComCodigo($usuario->getId(), $codigo);

        return $this->json(['existe' => $existe]);
    }

    #[Route('/api/gados/{id}/abate/cancelar', methods: ['PUT'])]
    public function cancelarAbate(Gado $gado, Request $request): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        $data = $this->decodeJsonPayload($request) ?? [];
        $novoCodigo = $data['novoCodigo'] ?? null;

        if ($novoCodigo !== null) {
            $novoCodigoValidado = filter_var($novoCodigo, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if ($novoCodigoValidado === false) {
                return $this->json(['error' => 'Novo código inválido'], 400);
            }

            $novoCodigo = $novoCodigoValidado;
        }

        try {
            $resultado = $this->gadoService->cancelarAbate($gado, $usuario->getId(), $novoCodigo);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if ($resultado) {
            return $this->json(['message' => 'Abate cancelado com sucesso']);
        }

        return $this->json(['error' => 'Não foi possível cancelar o abate'], 400);
    }

    #[Route('/api/gados/abate', methods: ['PUT'])]
    public function mandarParaAbate(Request $request): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        $data = $this->decodeJsonPayload($request);

        if (!$data || !isset($data['gados']) || !is_array($data['gados'])) {
            return $this->json(['error' => 'Envie uma lista válida de gados para abate.'], 400);
        }

        $idsGado = [];

        foreach ($data['gados'] as $idGado) {
            $idGadoValidado = filter_var($idGado, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if ($idGadoValidado === false) {
                return $this->json(['error' => 'A lista de gados contém um ou mais IDs inválidos.'], 400);
            }

            $idsGado[] = $idGadoValidado;
        }

        $idsGado = array_values(array_unique($idsGado));

        try {
            $resultado = $this->gadoService->mandarParaAbate(
                $idsGado,
                $usuario->getId()
            );
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if ($resultado) {
            return $this->json(['message' => 'Gados abatidos']);
        }

        return $this->json(['error' => 'Nenhum gado atualizado'], 400);
    }

    #[Route('/api/fazendas/{fazendaId}/gados', methods: ['POST'])]
    public function cadastrar(int $fazendaId, Request $request): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        $data = $this->decodeJsonPayload($request);

        if ($data === null) {
            return $this->json(['error' => 'Envie um JSON válido para cadastrar o gado.'], 400);
        }

        $data['fazendaId'] = $fazendaId;
        $dto = new GadoDTO();
        $form = $this->createJsonGadoForm($dto, $usuario->getId());
        $form->submit($data);

        if (!$form->isValid()) {
            return $this->buildFormErrorResponse($form);
        }

        try {
            $resultado = $this->gadoService->inserir($dto, $usuario->getId());
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if ($resultado) {
            return $this->json(['message' => 'Gado criado'], 201);
        }

        return $this->json(['error' => 'Não foi possível criar o gado.'], 400);
    }

    #[Route('/api/gados/{id}', methods: ['PUT'])]
    public function atualizar(Gado $gado, Request $request): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        $data = $this->decodeJsonPayload($request);

        if ($data === null) {
            return $this->json(['error' => 'Envie um JSON válido para atualizar o gado.'], 400);
        }

        $data['fazendaId'] ??= $gado->getFazenda()?->getId();
        $dto = new GadoDTO();
        $form = $this->createJsonGadoForm($dto, $usuario->getId());
        $form->submit($data);

        if (!$form->isValid()) {
            return $this->buildFormErrorResponse($form);
        }

        $dto->setId($gado->getId());

        try {
            $resultado = $this->gadoService->alterar($dto, $usuario->getId());
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if ($resultado) {
            return $this->json(['message' => 'Atualizado']);
        }

        return $this->json(['error' => 'Erro ao atualizar'], 400);
    }

    #[Route('/api/gados/{id}', methods: ['DELETE'])]
    public function deletar(Gado $gado): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        $resultado = $this->gadoService->excluir($gado, $usuario->getId());

        if ($resultado) {
            return $this->json(['message' => 'Removido']);
        }

        return $this->json(['error' => 'Erro ao deletar'], 400);
    }

    private function createJsonGadoForm(GadoDTO $dto, int $idUsuario): FormInterface
    {
        return $this->createForm(GadoType::class, $dto, [
            'csrf_protection' => false,
            'fazendas_choices' => $this->fazendaService->listarEntidades($idUsuario),
        ]);
    }
}
