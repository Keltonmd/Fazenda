<?php

namespace App\Controller\Api;

use App\Dto\UsuarioDTO;
use App\Entity\Usuario;
use App\Service\UsuarioService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UsuarioController extends AbstractController
{
    private UsuarioService $usuarioService;

    #[Autowire(service: 'limiter.cadastro')]
    private RateLimiterFactory $cadastroLimiter;

    public function __construct(UsuarioService $usuarioService, #[Autowire(service: 'limiter.cadastro')] RateLimiterFactory $cadastroLimiter)
    {
        $this->usuarioService = $usuarioService;
        $this->cadastroLimiter = $cadastroLimiter;
    }

    #[Route('/api/usuario', methods: ['GET'])]
    public function perfil(): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        return $this->json([
            'id' => $usuario->getId(),
            'nome' => $usuario->getNome(),
            'email' => $usuario->getEmail(),
        ]);
    }

    #[Route('/api/usuario', methods: ['POST'])]
    public function cadastrar(Request $request, ValidatorInterface $validator): Response
    {
        $ip = $request->getClientIp();

        $limiter = $this->cadastroLimiter->create($ip);
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            return $this->json([
                'error' => 'Muitas tentativas. Tente novamente mais tarde.'
            ], 429);
        }
    
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['nome'], $data['email'], $data['password'])) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        $dto = new UsuarioDTO();

        try {
            $dto->setNome($data['nome']);
            $dto->setEmail($data['email']);
            $dto->setPassword($data['password']);
        } catch (\TypeError $e) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json([
                'errors' => (string) $errors
            ], 400);
        }

        $resultado = $this->usuarioService->inserir($dto);

        if ($resultado) {
            return $this->json(['message' => 'Usuário criado'], 201);
        }

        return $this->json(['error' => 'Erro ao criar usuário'], 400);
    }

    #[Route('/api/usuario', methods: ['PUT'])]
    public function atualizar(Request $request, ValidatorInterface $validator): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['nome'], $data['email'])) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        $dto = new UsuarioDTO();

        try {
            $dto->setId($usuario->getId());
            $dto->setNome($data['nome']);
            $dto->setEmail($data['email']);
        } catch (\TypeError $e) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json([
                'errors' => (string) $errors
            ], 400);
        }

        $resultado = $this->usuarioService->alterar($dto);

        if ($resultado) {
            return $this->json(['message' => 'Usuário atualizado']);
        }

        return $this->json(['error' => 'Erro ao atualizar'], 400);
    }

    #[Route('/api/usuario', methods: ['DELETE'])]
    public function deletar(): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $resultado = $this->usuarioService->excluir($usuario->getId());

        if ($resultado) {
            return $this->json(['message' => 'Usuário deletado']);
        }

        return $this->json(['error' => 'Erro ao deletar'], 400);
    }

    #[Route('/api/usuario/password', methods: ['PUT'])]
    public function atualizarPassword(Request $request, ValidatorInterface $validator): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Não autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['password'])) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        if (!is_string($data['password'])) {
            return $this->json(['error' => 'Dados inválidos'], 400);
        }

        $errors = $validator->validatePropertyValue(UsuarioDTO::class, 'password', $data['password']);

        if (count($errors) > 0) {
            return $this->json([
                'errors' => (string) $errors
            ], 400);
        }

        $resultado = $this->usuarioService->alterarPassword(
            $usuario->getId(),
            $data['password']
        );

        if ($resultado) {
            return $this->json(['message' => 'Senha atualizada']);
        }

        return $this->json(['error' => 'Erro ao atualizar senha'], 400);
    }
}
