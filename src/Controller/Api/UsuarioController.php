<?php

namespace App\Controller\Api;

use App\Dto\UsuarioDTO;
use App\Form\UsuarioType;
use App\Service\UsuarioService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UsuarioController extends AbstractController
{
    use HandlesJsonFormRequestsTrait;

    private UsuarioService $usuarioService;

    #[Autowire(service: 'limiter.cadastro')]
    private RateLimiterFactory $cadastroLimiter;

    public function __construct(UsuarioService $usuarioService, #[Autowire(service: 'limiter.cadastro')] RateLimiterFactory $cadastroLimiter)
    {
        $this->usuarioService = $usuarioService;
        $this->cadastroLimiter = $cadastroLimiter;
    }

    #[Route('/api/usuario', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function perfil(): Response
    {
        $usuario = $this->getAuthenticatedUsuario();

        return $this->json([
            'id' => $usuario->getId(),
            'nome' => $usuario->getNome(),
            'email' => $usuario->getEmail(),
        ]);
    }

    #[Route('/api/usuario', methods: ['POST'])]
    public function cadastrar(Request $request): Response
    {
        $ip = $request->getClientIp();

        $limiter = $this->cadastroLimiter->create($ip);
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            return $this->json([
                'error' => 'Muitas tentativas. Tente novamente mais tarde.'
            ], 429);
        }
    
        $data = $this->decodeJsonPayload($request);

        if ($data === null) {
            return $this->json(['error' => 'Envie um JSON válido para cadastrar o usuário.'], 400);
        }

        $dto = new UsuarioDTO();
        $form = $this->createForm(UsuarioType::class, $dto, [
            'csrf_protection' => false,
        ]);
        $form->submit($data);

        if (!$form->isValid()) {
            return $this->buildFormErrorResponse($form);
        }

        try {
            $resultado = $this->usuarioService->inserir($dto);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if ($resultado) {
            return $this->json(['message' => 'Usuário criado'], 201);
        }

        return $this->json(['error' => 'Erro ao criar usuário'], 400);
    }

    #[Route('/api/usuario', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function atualizar(Request $request): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        $data = $this->decodeJsonPayload($request);

        if ($data === null) {
            return $this->json(['error' => 'Envie um JSON válido para atualizar o usuário.'], 400);
        }

        $dto = new UsuarioDTO();
        $form = $this->createForm(UsuarioType::class, $dto, [
            'csrf_protection' => false,
            'include_password' => false,
        ]);
        $form->submit($data);

        if (!$form->isValid()) {
            return $this->buildFormErrorResponse($form);
        }

        $dto->setId($usuario->getId());

        try {
            $resultado = $this->usuarioService->alterar($dto);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if ($resultado) {
            return $this->json(['message' => 'Usuário atualizado']);
        }

        return $this->json(['error' => 'Erro ao atualizar'], 400);
    }

    #[Route('/api/usuario', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deletar(): Response
    {
        $usuario = $this->getAuthenticatedUsuario();

        $resultado = $this->usuarioService->excluir($usuario->getId());

        if ($resultado) {
            return $this->json(['message' => 'Usuário deletado']);
        }

        return $this->json(['error' => 'Erro ao deletar'], 400);
    }

    #[Route('/api/usuario/password', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function atualizarPassword(Request $request): Response
    {
        $usuario = $this->getAuthenticatedUsuario();
        $data = $this->decodeJsonPayload($request);

        if ($data === null) {
            return $this->json(['error' => 'Envie um JSON válido para atualizar a senha.'], 400);
        }

        $dto = new UsuarioDTO();
        $form = $this->createForm(UsuarioType::class, $dto, [
            'csrf_protection' => false,
            'include_nome' => false,
            'include_email' => false,
            'password_require_complexity' => false,
        ]);
        $form->submit($data);

        if (!$form->isValid()) {
            return $this->buildFormErrorResponse($form);
        }

        $resultado = $this->usuarioService->alterarPassword(
            $usuario->getId(),
            $dto->getPassword()
        );

        if ($resultado) {
            return $this->json(['message' => 'Senha atualizada']);
        }

        return $this->json(['error' => 'Erro ao atualizar senha'], 400);
    }
}
