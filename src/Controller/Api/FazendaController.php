<?php

namespace App\Controller\Api;

use App\Dto\FazendaDTO;
use App\Entity\Fazenda;
use App\Form\FazendaType;
use App\Service\FazendaService;
use App\Service\VeterinarioService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class FazendaController extends AbstractController
{
    use HandlesJsonFormRequestsTrait;

    private FazendaService $fazendaService;
    private VeterinarioService $veterinarioService;

    public function __construct(FazendaService $fazendaService, VeterinarioService $veterinarioService)
    {
        $this->fazendaService = $fazendaService;
        $this->veterinarioService = $veterinarioService;
    }

    #[Route('/api/fazendas', methods: ['GET'])]
    public function listar(Request $request, PaginatorInterface $paginator): Response
    {
        $usuario = $this->getAuthenticatedUsuario();

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
        $usuario = $this->getAuthenticatedUsuario();

        return $this->json([
            'quantidadeFazendas' => $this->fazendaService->contFazendas($usuario->getId()),
        ]);
    }

    #[Route('/api/fazendas/ultimos-cadastros', methods: ['GET'])]
    public function ultimosCadastros(): Response {
        $usuario = $this->getAuthenticatedUsuario();

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
        $usuario = $this->getAuthenticatedUsuario();

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
    public function buscar(Fazenda $fazenda): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        $fazenda = $this->fazendaService->buscarPorId($fazenda, $usuario->getId());

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
    public function cadastrar(Request $request): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        $data = $this->decodeJsonPayload($request);

        if ($data === null) {
            return $this->json(['error' => 'Envie um JSON válido para cadastrar a fazenda.'], 400);
        }

        $dto = new FazendaDTO();
        $form = $this->createJsonFazendaForm($dto, $usuario->getId());
        $form->submit($data);

        if (!$form->isValid()) {
            return $this->buildFormErrorResponse($form);
        }

        try {
            $resultado = $this->fazendaService->inserir($dto, $usuario->getId());
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if ($resultado) {
            return $this->json(['message' => 'Fazenda criada'], 201);
        }

        return $this->json(['error' => 'Não foi possível criar a fazenda.'], 400);
    }

    #[Route('/api/fazendas/{id}', methods: ['PUT'])]
    public function atualizar(Fazenda $fazenda, Request $request): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        $data = $this->decodeJsonPayload($request);

        if ($data === null) {
            return $this->json(['error' => 'Envie um JSON válido para atualizar a fazenda.'], 400);
        }

        $dto = new FazendaDTO();
        $form = $this->createJsonFazendaForm($dto, $usuario->getId());
        $form->submit($data);

        if (!$form->isValid()) {
            return $this->buildFormErrorResponse($form);
        }

        $dto->setId($fazenda->getId());

        try {
            $resultado = $this->fazendaService->alterar($dto, $usuario->getId());
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if ($resultado) {
            return $this->json(['message' => 'Atualizada']);
        }

        return $this->json(['error' => 'Erro ao atualizar'], 400);
    }

    #[Route('/api/fazendas/{id}', methods: ['DELETE'])]
    public function deletar(Fazenda $fazenda): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        $resultado = $this->fazendaService->excluir($fazenda, $usuario->getId());

        if ($resultado) {
            return $this->json(['message' => 'Removida']);
        }

        return $this->json(['error' => 'Erro ao deletar'], 400);
    }

    private function createJsonFazendaForm(FazendaDTO $dto, int $idUsuario): FormInterface
    {
        return $this->createForm(FazendaType::class, $dto, [
            'csrf_protection' => false,
            'veterinarios_choices' => $this->veterinarioService->listarEntidades($idUsuario),
        ]);
    }
}
