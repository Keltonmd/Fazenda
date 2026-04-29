<?php

namespace App\Controller\Api;

use App\Dto\FazendaDTO;
use App\Entity\Usuario;
use App\Service\FazendaService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class FazendaController extends AbstractController
{
    private FazendaService $fazendaService;

    public function __construct(FazendaService $fazendaService)
    {
        $this->fazendaService = $fazendaService;
    }

    #[Route('/api/fazendas', methods: ['GET'])]
    public function listar(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $pagination = $this->fazendaService->listarTodosPaginado($usuario->getId(), $request, $paginator);

        $dados = [];

        foreach ($pagination as $fazenda) {
            $dados[] = [
                'id' => $fazenda->getId(),
                'nome' => $fazenda->getNome(),
                'responsavel' => $fazenda->getResponsavel(),
                'tamanhoHA' => $fazenda->getTamanhoHA()
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

    #[Route('/api/fazendas/contagem', methods: ['GET'])]
    public function contagem(): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        return $this->json([
            'quantidadeFazendas' => $this->fazendaService->contFazendas($usuario->getId()),
        ]);
    }

    #[Route('/api/fazendas/ultimos-cadastros', methods: ['GET'])]
    public function ultimosCadastros(): Response {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $dados = [];

        foreach ($this->fazendaService->listarUltimosCadastros($usuario->getId()) as $fazenda) {
            $dados[] = [
                'id' => $fazenda->getId(),
                'nome' => $fazenda->getNome(),
                'responsavel' => $fazenda->getResponsavel(),
                'tamanhoHA' => $fazenda->getTamanhoHA(),
            ];
        }

        return $this->json([
            'data' => $dados,
        ]);
    }

    #[Route('/api/fazendas/opcoes', methods: ['GET'])]
    public function opcoes(): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $dados = [];

        foreach ($this->fazendaService->listarOpcoes($usuario->getId()) as $fazenda) {
            $dados[] = [
                'id' => $fazenda->getId(),
                'nome' => $fazenda->getNome(),
            ];
        }

        return $this->json([
            'data' => $dados,
        ]);
    }

    #[Route('/api/fazendas/{id}', methods: ['GET'])]
    public function buscar(int $id): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $fazenda = $this->fazendaService->buscarPorId($id, $usuario->getId());

        if (!$fazenda) {
            return $this->json(['error' => 'Fazenda não encontrada'], 404);
        }

        return $this->json([
            'id' => $fazenda->getId(),
            'nome' => $fazenda->getNome(),
            'responsavel' => $fazenda->getResponsavel(),
            'tamanhoHA' => $fazenda->getTamanhoHA(),
        ]);
    }

    #[Route('/api/fazendas', methods: ['POST'])]
    public function cadastrar(Request $request, ValidatorInterface $validator): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['nome'], $data['responsavel'], $data['tamanhoHA'])) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        $dto = new FazendaDTO();

        try {
            $dto->setNome($data['nome']);
            $dto->setResponsavel($data['responsavel']);
            $dto->setTamanhoHA($data['tamanhoHA']);
        } catch (\TypeError $e) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json([
                'errors' => (string) $errors
            ], 400);
        }

        $resultado = $this->fazendaService->inserir($dto, $usuario->getId());

        if ($resultado) {
            return $this->json(['message' => 'Fazenda criada'], 201);
        }

        return $this->json(['error' => 'Nome inválido'], 400);
    }

    #[Route('/api/fazendas/{id}', methods: ['PUT'])]
    public function atualizar(int $id, Request $request, ValidatorInterface $validator): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['nome'], $data['responsavel'], $data['tamanhoHA'])) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        $dto = new FazendaDTO();

        try {
            $dto->setId($id);
            $dto->setNome($data['nome']);
            $dto->setResponsavel($data['responsavel']);
            $dto->setTamanhoHA($data['tamanhoHA']);
        } catch (\TypeError $e) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json([
                'errors' => (string) $errors
            ], 400);
        }

        $resultado = $this->fazendaService->alterar($dto, $usuario->getId());

        if ($resultado) {
            return $this->json(['message' => 'Atualizada']);
        }

        return $this->json(['error' => 'Erro ao atualizar'], 400);
    }

    #[Route('/api/fazendas/{id}', methods: ['DELETE'])]
    public function deletar(int $id): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $resultado = $this->fazendaService->excluir($id, $usuario->getId());

        if ($resultado) {
            return $this->json(['message' => 'Removida']);
        }

        return $this->json(['error' => 'Erro ao deletar'], 400);
    }
}
