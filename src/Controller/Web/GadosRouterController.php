<?php

namespace App\Controller\Web;

use App\Entity\Usuario;
use App\Form\GadoType;
use App\Service\FazendaService;
use App\Service\GadoService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GadosRouterController extends AbstractController
{
    private GadoService $gadoService;
    private FazendaService $fazendaService;

    public function __construct(GadoService $gadoService, FazendaService $fazendaService)
    {
        $this->gadoService = $gadoService;
        $this->fazendaService = $fazendaService;
    }

    #[Route('/fazendas/gados', name: 'gados_index')]
    public function gados(Request $request, PaginatorInterface $paginator): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('login');
        }

        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        $fazendasChoices = $this->fazendaService->listarEntidades($usuario->getId());

        $form = $this->createForm(GadoType::class, null, [
            'fazendas_choices' => $fazendasChoices,
        ]);
        $form->handleRequest($request);

        $statusCode = Response::HTTP_OK;

        if ($form->isSubmitted() && !$form->isValid()) {
            $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        $pagination = $this->gadoService->listarGadosVivosPaginado($usuario->getId(), $request, $paginator);
        $fazendasOpcoes = $this->fazendaService->listarOpcoes($usuario->getId());

        return $this->render('/web/gados/gados.html.twig', [
            'gados' => $pagination,
            'fazendasOpcoes' => $fazendasOpcoes,
            'gadoForm' => $form->createView(),
        ], new Response(status: $statusCode));
    }

    #[Route('/fazendas/gados/abates', name: 'abates_index')]
    public function abates(Request $request, PaginatorInterface $paginator): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('login');
        }

        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        $abaAtiva = $request->query->get('aba') === 'abatidos' ? 'abatidos' : 'para_abate';
        $listaAtiva = $abaAtiva === 'abatidos'
            ? $this->gadoService->listarGadosAbatidosPaginado($usuario->getId(), $request, $paginator)
            : $this->gadoService->listarGadosParaAbatePaginado($usuario->getId(), $request, $paginator);
        $fazendasOpcoes = $this->fazendaService->listarOpcoes($usuario->getId());
        $fazendasPorId = [];

        foreach ($fazendasOpcoes as $fazenda) {
            $fazendasPorId[$fazenda->getId()] = $fazenda->getNome();
        }

        return $this->render('/web/gados/abates.html.twig', [
            'abaAtiva' => $abaAtiva,
            'listaAtiva' => $listaAtiva,
            'fazendasOpcoes' => $fazendasOpcoes,
            'fazendasPorId' => $fazendasPorId,
            'resumoAbates' => $this->gadoService->resumoAbates($usuario->getId()),
        ]);
    }

}
