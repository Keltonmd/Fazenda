<?php

namespace App\Controller\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthRouterController extends AbstractController
{

    #[Route('/', name: 'login')]
    public function login(): Response
    {
        return $this->render('/web/auth/login.html.twig', [
            'controller_name' => 'AuthRouterController',
        ]);
    }

    #[Route('/register', name: 'register')]
    public function register(): Response
    {
        return $this->render('/web/auth/register.html.twig', [
            'controller_name' => 'AuthRouterController',
        ]);
    }

}
