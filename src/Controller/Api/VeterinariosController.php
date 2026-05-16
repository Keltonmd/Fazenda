<?php

namespace App\Controller\Api;

use App\Dto\VeterinarioDTO;
use App\Entity\Fazenda;
use App\Entity\Veterinario;
use App\Form\VeterinarioType;
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
final class VeterinariosController extends AbstractController
{
    use HandlesJsonFormRequestsTrait;

    private VeterinarioService $veterinarioService;
    private FazendaService $fazendaService;

    public function __construct(VeterinarioService $veterinarioService, FazendaService $fazendaService)
    {
        $this->veterinarioService = $veterinarioService;
        $this->fazendaService = $fazendaService;
    }

    #[Route('/api/veterinarios', methods: ['GET'])]
    public function listar(Request $request, PaginatorInterface $paginator): Response {
        $usuario = $this->getAuthenticatedUsuario();

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
        $usuario = $this->getAuthenticatedUsuario();

        return $this->json([
            'quantidadeVeterinarios' => $this->veterinarioService->contVeterinarios($usuario->getId()),
        ]);
    }

    #[Route('/api/veterinarios/ultimos-cadastros', methods: ['GET'])]
    public function ultimosCadastros(): Response {
        $usuario = $this->getAuthenticatedUsuario();

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
    public function cadastrar(Request $request): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        $data = $this->decodeJsonPayload($request);

        if ($data === null) {
            return $this->json(['error' => 'Envie um JSON válido para cadastrar o veterinário.'], 400);
        }

        $dto = new VeterinarioDTO();
        $form = $this->createJsonVeterinarioForm($dto, $usuario->getId());
        $form->submit($data);

        if (!$form->isValid()) {
            return $this->buildFormErrorResponse($form);
        }

        try {
            $resultado = $this->veterinarioService->inserir($dto, $usuario->getId());
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if ($resultado) {
            return $this->json(['message' => 'Veterinário criado'], 201);
        }

        return $this->json(['error' => 'Não foi possível criar o veterinário.'], 400);
    }

    #[Route('/api/veterinarios/{id}', methods: ['PUT'])]
    public function atualizar(Veterinario $veterinario, Request $request): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        $data = $this->decodeJsonPayload($request);

        if ($data === null) {
            return $this->json(['error' => 'Envie um JSON válido para atualizar o veterinário.'], 400);
        }

        $dto = new VeterinarioDTO();
        $form = $this->createJsonVeterinarioForm($dto, $usuario->getId());
        $form->submit($data);

        if (!$form->isValid()) {
            return $this->buildFormErrorResponse($form);
        }

        $dto->setId($veterinario->getId());

        try {
            $resultado = $this->veterinarioService->alterar($dto, $usuario->getId());
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if ($resultado) {
            return $this->json(['message' => 'Atualizado com sucesso']);
        }

        return $this->json(['error' => 'Erro ao atualizar'], 400);
    }

    #[Route('/api/veterinarios/{id}', methods: ['DELETE'])]
    public function deletar(Veterinario $veterinario): Response {
        $usuario = $this->getAuthenticatedUsuario();
        $resultado = $this->veterinarioService->excluir($veterinario, $usuario->getId());

        if ($resultado) {
            return $this->json(['message' => 'Removido com sucesso']);
        }

        return $this->json(['error' => 'Erro ao deletar'], 400);
    }

    #[Route('/api/veterinarios/{veterinario}/fazendas/{fazenda}', methods: ['POST'])]
    public function adicionarFazenda(Veterinario $veterinario, Fazenda $fazenda): Response {
        $usuario = $this->getAuthenticatedUsuario();
        $resultado = $this->veterinarioService->adicionarFazenda($veterinario, $fazenda, $usuario->getId());

        if ($resultado) {
            return $this->json(['message' => 'Fazenda vinculada']);
        }

        return $this->json(['error' => 'Erro ao vincular'], 400);
    }

    #[Route('/api/veterinarios/{veterinario}/fazendas/{fazenda}', methods: ['DELETE'])]
    public function removerFazenda(Veterinario $veterinario, Fazenda $fazenda): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        $resultado = $this->veterinarioService->removerFazenda($veterinario, $fazenda, $usuario->getId());

        if ($resultado) {
            return $this->json(['message' => 'Fazenda removida']);
        }

        return $this->json(['error' => 'Erro ao remover'], 400);
    }

    private function createJsonVeterinarioForm(VeterinarioDTO $dto, int $idUsuario): FormInterface
    {
        return $this->createForm(VeterinarioType::class, $dto, [
            'csrf_protection' => false,
            'fazendas_choices' => $this->fazendaService->listarEntidades($idUsuario),
        ]);
    }
}
