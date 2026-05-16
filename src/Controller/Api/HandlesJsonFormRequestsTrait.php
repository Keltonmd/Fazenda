<?php

namespace App\Controller\Api;

use App\Entity\Usuario;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait HandlesJsonFormRequestsTrait
{
    private function getAuthenticatedUsuario(): Usuario
    {
        $usuario = $this->getUser();

        if (!$usuario instanceof Usuario) {
            throw $this->createAccessDeniedException('Usuário não autenticado.');
        }

        return $usuario;
    }

    private function decodeJsonPayload(Request $request): ?array
    {
        $data = json_decode($request->getContent(), true);

        return is_array($data) ? $data : null;
    }

    private function buildFormErrorResponse(FormInterface $form, int $status = 400): JsonResponse
    {
        return $this->json([
            'errors' => $this->formatFormErrors($form),
        ], $status);
    }

    private function formatFormErrors(FormInterface $form): array
    {
        $messages = [];

        foreach ($form->getErrors() as $error) {
            $messages[] = $error->getMessage();
        }

        foreach ($form->all() as $child) {
            foreach ($this->formatFormErrors($child) as $message) {
                $messages[] = $message;
            }
        }

        return array_values(array_unique(array_filter($messages)));
    }
}
