<?php

namespace App\Controller\Web;

use App\Form\LoginType;
use App\Form\UsuarioType;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthRouterController extends AbstractController
{
    #[Route('/', name: 'login')]
    public function login(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }

        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);

        $statusCode = Response::HTTP_OK;

        if ($form->isSubmitted() && !$form->isValid()) {
            $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        return $this->render('/web/auth/login.html.twig', [
            'loginForm' => $form->createView(),
        ], new Response(status: $statusCode));
    }

    #[Route('/register', name: 'register')]
    public function register(Request $request): Response
    {
        $form = $this->createForm(UsuarioType::class);
        $form->handleRequest($request);

        $statusCode = Response::HTTP_OK;

        if ($form->isSubmitted() && !$form->isValid()) {
            $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        return $this->render('/web/auth/register.html.twig', [
            'registerForm' => $form->createView(),
        ], new Response(status: $statusCode));
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new LogicException('O logout e interceptado pelo firewall.');
    }

}
