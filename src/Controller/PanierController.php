<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Repository\PanierRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PanierController extends AbstractController
{
    #[Route('/panier/{user}', name: 'app_panier', requirements: ['user' => '\\d+'], methods: ['GET'])]
    public function index(PanierRepository $repository, Security $security, EntityManagerInterface $em): Response
    {
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $panier = $repository->findOneBy(['user' => $user]);
        if (!$panier) {
            $panier = new Panier();
            $panier->setUser($user);
            $em->persist($panier);
            $em->flush();
        }

        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
            'user' => $user,
        ]);
    }

    #[Route('/panier/{user}/add', name: 'app_panier_add', requirements: ['user' => '\\d+'], methods: ['POST'])]
    public function addProduct(
        Request $request,
        PanierRepository $panierRepository,
        ProductRepository $productRepository,
        EntityManagerInterface $em,
        Security $security
    ): Response {
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $productId = (int) $request->request->get('product_id');
        if (!$this->isCsrfTokenValid('cart_add_' . $productId, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid security token.');
            return $this->redirectToRoute('product');
        }

        $product = $productRepository->find($productId);
        $panier = $panierRepository->findOneBy(['user' => $user]);

        if (!$panier) {
            $panier = new Panier();
            $panier->setUser($user);
            $em->persist($panier);
        }

        if (!$product || !$product->isActive()) {
            $this->addFlash('danger', 'This product is no longer available.');
            return $this->redirectToRoute('product');
        }

        if (($product->getQuantity() ?? 0) <= 0) {
            $this->addFlash('danger', 'This product is out of stock.');
            return $this->redirectToRoute('product');
        }

        if ($panier->getProducts()->contains($product)) {
            $this->addFlash('warning', 'This product is already in your cart.');
            return $this->redirectToRoute('product');
        }

        $panier->addProduct($product);
        $em->flush();

        $this->addFlash('success', 'Product added to cart.');
        return $this->redirectToRoute('product');
    }

    #[Route('/panier/{user}/remove', name: 'app_panier_remove', requirements: ['user' => '\\d+'], methods: ['POST'])]
    public function removeProduct(
        Request $request,
        PanierRepository $panierRepository,
        ProductRepository $productRepository,
        EntityManagerInterface $em,
        Security $security
    ): Response {
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $productId = (int) $request->request->get('product_id');
        if (!$this->isCsrfTokenValid('cart_remove_' . $productId, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid security token.');
            return $this->redirectToRoute('app_panier', ['user' => $user->getId()]);
        }

        $panier = $panierRepository->findOneBy(['user' => $user]);
        $product = $productRepository->find($productId);

        if ($panier && $product) {
            $panier->removeProduct($product);
            $em->flush();
            $this->addFlash('success', 'Product removed from cart.');
        }

        return $this->redirectToRoute('app_panier', ['user' => $user->getId()]);
    }
}

# backdated-commit: 2025-08-28 00:00:00
