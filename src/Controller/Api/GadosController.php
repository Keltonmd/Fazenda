<?php

namespace App\Controller\Api;

use App\Dto\GadoDTO;
use App\Entity\Usuario;
use App\Service\GadoService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class GadosController extends AbstractController
{
    private GadoService $gadoService;

    public function __construct(GadoService $gadoService)
    {
        $this->gadoService = $gadoService;
    }

    #[Route('/api/gados', methods: ['GET'])]
    public function listar(Request $request, PaginatorInterface $paginator): Response {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

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
    public function listarParaAbate(Request $request, PaginatorInterface $paginator): Response {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

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
    public function listarAbatidos(Request $request, PaginatorInterface $paginator): Response {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

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
    public function listarVivos(Request $request, PaginatorInterface $paginator): Response {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

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
    public function resumo(): Response {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

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
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        return $this->json($this->gadoService->resumoAbates($usuario->getId()));
    }

    #[Route('/api/gados/ultimos-cadastros', methods: ['GET'])]
    public function ultimosCadastros(): Response {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

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
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        if ($codigo <= 0) {
            return $this->json(['error' => 'Código inválido'], 400);
        }

        $existe = $this->gadoService->existeGadoComCodigo($usuario->getId(), $codigo);

        return $this->json(['existe' => $existe]);
    }

    #[Route('/api/gados/{id}/abate/cancelar', methods: ['PUT'])]
    public function cancelarAbate(int $id, Request $request): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $novoCodigo = $data['novoCodigo'] ?? null;

        if ($novoCodigo !== null) {
            $novoCodigoValidado = filter_var($novoCodigo, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if ($novoCodigoValidado === false) {
                return $this->json(['error' => 'Novo código inválido'], 400);
            }

            $novoCodigo = $novoCodigoValidado;
        }

        try {
            $resultado = $this->gadoService->cancelarAbate($id, $usuario->getId(), $novoCodigo);
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
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['gados']) || !is_array($data['gados'])) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        $idsGado = [];

        foreach ($data['gados'] as $idGado) {
            $idGadoValidado = filter_var($idGado, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if ($idGadoValidado === false) {
                return $this->json(['error' => 'Dados inválidos'], 400);
            }

            $idsGado[] = $idGadoValidado;
        }

        $idsGado = array_values(array_unique($idsGado));

        $resultado = $this->gadoService->mandarParaAbate(
            $idsGado,
            $usuario->getId()
        );

        if ($resultado) {
            return $this->json(['message' => 'Gados abatidos']);
        }

        return $this->json(['error' => 'Nenhum gado atualizado'], 400);
    }

    #[Route('/api/fazendas/{fazendaId}/gados', methods: ['POST'])]
    public function cadastrar(int $fazendaId, Request $request, ValidatorInterface $validator): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['codigo'], $data['leite'], $data['racao'], $data['peso'], $data['nascimento'])) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        if (!is_string($data['nascimento'])) {
            return $this->json(['error' => 'Data inválida'], 400);
        }

        try {
            $nascimento = new \DateTimeImmutable($data['nascimento']);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Data inválida'], 400);
        }

        $dto = new GadoDTO();

        try {
            $dto->setCodigo($data['codigo']);
            $dto->setLeite($data['leite']);
            $dto->setRacao($data['racao']);
            $dto->setPeso($data['peso']);
            $dto->setNascimento($nascimento);
            $dto->setFazendaId($fazendaId);
        } catch (\TypeError $e) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json([
                'errors' => (string) $errors
            ], 400);
        }

        $resultado = $this->gadoService->inserir($dto, $fazendaId, $usuario->getId());

        if ($resultado) {
            return $this->json(['message' => 'Gado criado'], 201);
        }

        return $this->json(['error' => 'Erro ao criar'], 400);
    }

    #[Route('/api/gados/{id}', methods: ['PUT'])]
    public function atualizar(int $id, Request $request, ValidatorInterface $validator): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['codigo'], $data['leite'], $data['racao'], $data['peso'], $data['nascimento'])) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        if (!is_string($data['nascimento'])) {
            return $this->json(['error' => 'Data inválida'], 400);
        }

        try {
            $nascimento = new \DateTimeImmutable($data['nascimento']);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Data inválida'], 400);
        }

        $dto = new GadoDTO();

        try {
            $dto->setId($id);
            $dto->setCodigo($data['codigo']);
            $dto->setLeite($data['leite']);
            $dto->setRacao($data['racao']);
            $dto->setPeso($data['peso']);
            $dto->setNascimento($nascimento);
        } catch (\TypeError $e) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json([
                'errors' => (string) $errors
            ], 400);
        }

        $resultado = $this->gadoService->alterar($dto, $usuario->getId());

        if ($resultado) {
            return $this->json(['message' => 'Atualizado']);
        }

        return $this->json(['error' => 'Erro ao atualizar'], 400);
    }

    #[Route('/api/gados/{id}', methods: ['DELETE'])]
    public function deletar(int $id): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $resultado = $this->gadoService->excluir($id, $usuario->getId());

        if ($resultado) {
            return $this->json(['message' => 'Removido']);
        }

        return $this->json(['error' => 'Erro ao deletar'], 400);
    }
}
