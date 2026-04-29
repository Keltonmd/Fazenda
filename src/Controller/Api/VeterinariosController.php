<?php

namespace App\Controller\Api;

use App\Dto\VeterinarioDTO;
use App\Entity\Usuario;
use App\Service\VeterinarioService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class VeterinariosController extends AbstractController
{
    private VeterinarioService $veterinarioService;

    public function __construct(VeterinarioService $veterinarioService)
    {
        $this->veterinarioService = $veterinarioService;
    }

    #[Route('/api/veterinarios', methods: ['GET'])]
    public function listar(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $pagination = $this->veterinarioService
            ->listarTodosVeterinariosPaginado($usuario->getId(), $request, $paginator);

        $dados = [];

        foreach ($pagination as $v) {
            $dados[] = [
                'id' => $v->getId(),
                'nome' => $v->getNome(),
                'crmv' => $v->getCrmv(),
                'fazendas' => $v->getFazendas(),
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

    #[Route('/api/veterinarios/contagem', methods: ['GET'])]
    public function contagem(): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        return $this->json([
            'quantidadeVeterinarios' => $this->veterinarioService->contVeterinarios($usuario->getId()),
        ]);
    }

    #[Route('/api/veterinarios/ultimos-cadastros', methods: ['GET'])]
    public function ultimosCadastros(): Response {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $dados = [];

        foreach ($this->veterinarioService->listarUltimosCadastros($usuario->getId()) as $veterinario) {
            $dados[] = [
                'id' => $veterinario->getId(),
                'nome' => $veterinario->getNome(),
                'crmv' => $veterinario->getCrmv(),
                'fazendas' => $veterinario->getFazendas(),
            ];
        }

        return $this->json([
            'data' => $dados,
        ]);
    }

    #[Route('/api/veterinarios', methods: ['POST'])]
    public function cadastrar(Request $request, ValidatorInterface $validator): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['nome'], $data['crmv'])) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        $dto = new VeterinarioDTO();

        try {
            $dto->setNome($data['nome']);
            $dto->setCrmv($data['crmv']);
        } catch (\TypeError $e) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }
        
        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json([
                'errors' => (string) $errors
            ], 400);
        }

        $idFazenda = $data['idFazenda'] ?? null;

        if ($idFazenda !== null) {
            $idFazendaValidado = filter_var($idFazenda, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if ($idFazendaValidado === false) {
                return $this->json(['error' => 'Fazenda inválida'], 400);
            }

            $idFazenda = $idFazendaValidado;
        }

        $resultado = $this->veterinarioService->inserir(
            $dto,
            $usuario->getId(),
            $idFazenda
        );

        if ($resultado) {
            return $this->json(['message' => 'Veterinário criado'], 201);
        }

        return $this->json(['error' => 'CRMV Inválido'], 400);
    }

    #[Route('/api/veterinarios/{id}', methods: ['PUT'])]
    public function atualizar(int $id, Request $request, ValidatorInterface $validator): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['nome'], $data['crmv'])) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        $dto = new VeterinarioDTO();

        try {
            $dto->setId($id);
            $dto->setNome($data['nome']);
            $dto->setCrmv($data['crmv']);
        } catch (\TypeError $e) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json([
                'errors' => (string) $errors
            ], 400);
        }

        $resultado = $this->veterinarioService->alterar($dto, $usuario->getId());

        if ($resultado) {
            return $this->json(['message' => 'Atualizado com sucesso']);
        }

        return $this->json(['error' => 'Erro ao atualizar'], 400);
    }

    #[Route('/api/veterinarios/{id}', methods: ['DELETE'])]
    public function deletar(int $id): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $resultado = $this->veterinarioService->excluir($id, $usuario->getId());

        if ($resultado) {
            return $this->json(['message' => 'Removido com sucesso']);
        }

        return $this->json(['error' => 'Erro ao deletar'], 400);
    }

    #[Route('/api/veterinarios/{id}/fazendas/{fazendaId}', methods: ['POST'])]
    public function adicionarFazenda(int $id, int $fazendaId): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $resultado = $this->veterinarioService->adicionarFazenda(
            $id,
            $fazendaId,
            $usuario->getId()
        );

        if ($resultado) {
            return $this->json(['message' => 'Fazenda vinculada']);
        }

        return $this->json(['error' => 'Erro ao vincular'], 400);
    }

    #[Route('/api/veterinarios/{id}/fazendas/{fazendaId}', methods: ['DELETE'])]
    public function removerFazenda(int $id, int $fazendaId): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $resultado = $this->veterinarioService->removerFazenda(
            $id,
            $fazendaId,
            $usuario->getId()
        );

        if ($resultado) {
            return $this->json(['message' => 'Fazenda removida']);
        }

        return $this->json(['error' => 'Erro ao remover'], 400);
    }
}
