<?php

namespace App\Controller;

use App\Entity\Product;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

class FavoriteController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

    #[Route('/product/{id}/favorite', name: 'favorite_add', methods: ['POST'])]
    public function addFavorite(int $id, Request $request): RedirectResponse|JsonResponse
    {
        $user = $this->getUser();
        if (! $user) {
            return $this->redirectToRoute('app_login');
        }

        $product = $this->em->getRepository(Product::class)->find($id);
        if ($product && ! $user->getFavorites()->contains($product)) {
            $user->addFavorite($product);
            $this->em->flush();
            $added = true;
        } else {
            $added = false;
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => $added,
                'favorite' => true,
                'productId' => $id,
            ]);
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('product'));
    }

    #[Route('/product/{id}/unfavorite', name: 'favorite_remove', methods: ['POST'])]
    public function removeFavorite(int $id, Request $request): RedirectResponse|JsonResponse
    {
        $user = $this->getUser();
        if (! $user) {
            return $this->redirectToRoute('app_login');
        }

        $product = $this->em->getRepository(Product::class)->find($id);
        if ($product && $user->getFavorites()->contains($product)) {
            $user->removeFavorite($product);
            $this->em->flush();
            $removed = true;
        } else {
            $removed = false;
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => $removed,
                'favorite' => false,
                'productId' => $id,
            ]);
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('product'));
    }

    #[Route('/favorites', name: 'favorite_list', methods: ['GET'])]
    public function listFavorites(): Response
    {
        $user = $this->getUser();
        if (! $user) {
            return $this->redirectToRoute('app_login');
        }

        $favorites = $user->getFavorites();

        return $this->render('favorite/list.html.twig', [
            'favorites' => $favorites,
        ]);
    }
}
