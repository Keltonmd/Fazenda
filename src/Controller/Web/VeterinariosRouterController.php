<?php

namespace App\Controller\Web;

use App\Entity\Usuario;
use App\Form\VeterinarioType;
use App\Service\FazendaService;
use App\Service\VeterinarioService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VeterinariosRouterController extends AbstractController
{
    private VeterinarioService $veterinarioService;
    private FazendaService $fazendaService;

    public function __construct(VeterinarioService $veterinarioService, FazendaService $fazendaService)
    {
        $this->veterinarioService = $veterinarioService;
        $this->fazendaService = $fazendaService;
    }

    #[Route('/fazendas/veterinarios', name: 'veterinarios_index')]
    public function veterinarios(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->redirectToRoute('login');
        }

        $pagination = $this->veterinarioService
            ->listarTodosVeterinariosPaginado($usuario->getId(), $request,$paginator);

        $fazendasChoices = $this->fazendaService->listarEntidades($usuario->getId());

        $form = $this->createForm(VeterinarioType::class, null, [
            'fazendas_choices' => $fazendasChoices,
        ]);
        $form->handleRequest($request);

        $statusCode = Response::HTTP_OK;

        if ($form->isSubmitted() && !$form->isValid()) {
            $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        }
        
        $fazendasOpcoes = $this->fazendaService->listarOpcoes($usuario->getId());

        return $this->render('/web/veterinarios/veterinarios.html.twig', [
            'veterinarios'    => $pagination,
            'veterinarioForm' => $form->createView(),
            'fazendasOpcoes'  => $fazendasOpcoes,
        ], new Response(status: $statusCode));
    }

}
