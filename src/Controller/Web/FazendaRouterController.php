<?php

namespace App\Controller\Web;

use App\Entity\Usuario;
use App\Form\FazendaType;
use App\Service\FazendaService;
use App\Service\GadoService;
use App\Service\VeterinarioService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FazendaRouterController extends AbstractController
{
    private FazendaService $fazendaService;
    private VeterinarioService $veterinarioService;
    private GadoService $gadoService;

    public function __construct(
        FazendaService $fazendaService,
        VeterinarioService $veterinarioService,
        GadoService $gadoService,
    ) {
        $this->fazendaService = $fazendaService;
        $this->veterinarioService = $veterinarioService;
        $this->gadoService = $gadoService;
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('login');
        }

        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $idUsuario = $usuario->getId();

        $contGados       = $this->gadoService->contGados($idUsuario);
        $contFazendas    = $this->fazendaService->contFazendas($idUsuario);
        $contVeterinarios = $this->veterinarioService->contVeterinarios($idUsuario);

        $leiteSemanal     = $this->gadoService->calcularLeiteSemanalPorUsuario($idUsuario);
        $racaoSemanal     = $this->gadoService->calcularRacaoSemanalPorUsuario($idUsuario);
        $animaisElegiveis = $this->gadoService->contarAnimaisElegiveis($idUsuario);

        $ultimosGados        = $this->gadoService->listarUltimosCadastros($idUsuario);
        $ultimasFazendas     = $this->fazendaService->listarUltimosCadastros($idUsuario);
        $ultimosVeterinarios = $this->veterinarioService->listarUltimosCadastros($idUsuario);

        return $this->render('/web/fazenda/dashboard.html.twig', [
            'gadosVivos'       => $contGados['vivos'],
            'gadosAbatidos'    => $contGados['abatidos'],
            'totalFazendas'    => $contFazendas,
            'totalVeterinarios' => $contVeterinarios,
            'leiteSemanal'     => $leiteSemanal,
            'racaoSemanal'     => $racaoSemanal,
            'animaisElegiveis' => $animaisElegiveis,
            'ultimosGados'     => $ultimosGados,
            'ultimasFazendas'  => $ultimasFazendas,
            'ultimosVeterinarios' => $ultimosVeterinarios,
        ]);
    }

    #[Route('/fazendas', name: 'fazenda_index')]
    public function fazendas(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$usuario) {
            return $this->redirectToRoute('login');
        }

        $veterinariosOpcoes = $this->veterinarioService->listarEntidades($usuario->getId());

        $form = $this->createForm(FazendaType::class, null, [
            'veterinarios_choices' => $veterinariosOpcoes,
        ]);
        $form->handleRequest($request);
        
        $statusCode = Response::HTTP_OK;

        if ($form->isSubmitted() && !$form->isValid()) {
            $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        $pagination = $this->fazendaService->listarTodosPaginado($usuario->getId(), $request, $paginator);

        return $this->render('/web/fazenda/fazendas.html.twig', [
            'fazendas' => $pagination,
            'fazendaForm' => $form->createView(),
        ], new Response(status: $statusCode));
    }
}
