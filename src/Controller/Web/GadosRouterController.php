<?php

namespace App\Controller\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GadosRouterController extends AbstractController
{

    #[Route('/fazendas/gados', name: 'gados_index')]
    public function gados(): Response
    {
        return $this->render('/web/gados/gados.html.twig', [
            'controller_name' => 'GadosRouterController',
        ]);
    }

    #[Route('/fazendas/gados/abates', name: 'abates_index')]
    public function abates(): Response
    {
        return $this->render('/web/gados/abates.html.twig');
    }

}
