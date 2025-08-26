<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FavoriteController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/product/{id}/favorite', name: 'favorite_add', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function addFavorite(int $id, Request $request): RedirectResponse|JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('add' . $id, $this->getSubmittedToken($request))) {
            return $this->favoriteResponse($request, false, false, $id, 'Invalid security token.');
        }

        $product = $this->em->getRepository(Product::class)->find($id);
        if (!$product || !$product->isActive()) {
            return $this->favoriteResponse($request, false, false, $id, 'Product unavailable.');
        }

        if (!$user->getFavorites()->contains($product)) {
            $user->addFavorite($product);
            $this->em->flush();
        }

        return $this->favoriteResponse($request, true, true, $id);
    }

    #[Route('/product/{id}/unfavorite', name: 'favorite_remove', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function removeFavorite(int $id, Request $request): RedirectResponse|JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('remove' . $id, $this->getSubmittedToken($request))) {
            return $this->favoriteResponse($request, false, true, $id, 'Invalid security token.');
        }

        $product = $this->em->getRepository(Product::class)->find($id);
        if ($product && $user->getFavorites()->contains($product)) {
            $user->removeFavorite($product);
            $this->em->flush();
        }

        return $this->favoriteResponse($request, true, false, $id);
    }

    #[Route('/favorites', name: 'favorite_list', methods: ['GET'])]
    public function listFavorites(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('favorite/list.html.twig', [
            'favorites' => $user->getFavorites(),
        ]);
    }

    private function getSubmittedToken(Request $request): string
    {
        $token = $request->request->get('_token');
        if (is_string($token) && $token !== '') {
            return $token;
        }

        $data = json_decode($request->getContent(), true);
        return is_array($data) ? (string) ($data['_token'] ?? '') : '';
    }

    private function favoriteResponse(Request $request, bool $success, bool $favorite, int $productId, ?string $message = null): RedirectResponse|JsonResponse
    {
        if ($request->isXmlHttpRequest()) {
            $payload = [
                'success' => $success,
                'favorite' => $favorite,
                'productId' => $productId,
            ];

            if ($message) {
                $payload['message'] = $message;
            }

            return $this->json($payload, $success ? 200 : 400);
        }

        if ($message) {
            $this->addFlash($success ? 'success' : 'danger', $message);
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('product'));
    }
}

# backdated-commit: 2025-08-26 00:00:00
