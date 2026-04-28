<?php

namespace App\Controller\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VeterinariosRouterController extends AbstractController
{

    #[Route('/fazendas/veterinarios', name: 'veterinarios_index')]
    public function veterinarios(): Response
    {
        return $this->render('/web/veterinarios/veterinarios.html.twig', [
            'controller_name' => 'VeterinariosRouterController',
        ]);
    }

}
