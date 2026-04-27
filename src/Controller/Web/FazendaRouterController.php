<?php

namespace App\Controller\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FazendaRouterController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        return $this->render('/web/fazenda/dashboard.html.twig', [
            'controller_name' => 'FazendaRouterController',
        ]);
    }

    #[Route('/fazendas/form', name: 'fazendaForm')]
    public function fazendaForm(): Response
    {
        return $this->render('/web/fazenda/fazendaForm.html.twig', [
            'controller_name' => 'FazendaRouterController',
        ]);
    } 

    #[Route('/fazendas', name: 'fazenda_index')]
    public function fazendas(): Response
    {
        return $this->render('/web/fazenda/fazendas.html.twig', [
            'controller_name' => 'FazendaRouterController',
        ]);
    }
}
