<?php

namespace App\Controller;

use App\Entity\Product;
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
    #[Route('/panier/{user}', name: 'app_panier')]
    public function index(PanierRepository $repository, Security $security): Response
    {
        $user   = $security->getUser();
        $panier = $repository->findOneBy(['user' => $user]);

        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
            'user'   => $user,
        ]);
    }

    #[Route('/panier/{user}/add', name: 'app_panier_add', methods: ['POST'])]
    public function addProduct(
        Request $request,
        PanierRepository $panierRepository,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        $productId = $request->request->get('product_id');
        $user      = $security->getUser();
        $panier    = $panierRepository->findOneBy(['user' => $user]);
        $product   = $productRepository->find($productId);

        // If panier or product not found
        if (! $panier || ! $product) {
            $this->addFlash('danger', 'Impossible d’ajouter le produit au panier.');
            return $this->redirectToRoute('product');
        }

        // 1. Check stock quantity
        if ($product->getQuantity() === 0) {
            $this->addFlash('danger', 'Le stock est vide de ce produit');
            return $this->redirectToRoute('product');
        }

        // 2. Add to panier if in stock
        $panier->addProduct($product);
        $entityManager->persist($panier);
        $entityManager->flush();

        $this->addFlash('success', 'Produit ajouté au panier avec succès.');
        return $this->redirectToRoute('product');
    }

    #[Route('/panier/{user}/remove', name: 'app_panier_remove', methods: ['DELETE', 'POST'])]
    public function removeProduct(
        Request $request,
        PanierRepository $panierRepository,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        $productId = $request->request->get('product_id');
        $user      = $security->getUser();
        $panier    = $panierRepository->findOneBy(['user' => $user]);
        $product   = $productRepository->find($productId);

        if ($panier && $product) {
            $panier->removeProduct($product);
            $entityManager->persist($panier);
            $entityManager->flush();
            $this->addFlash('success', 'Product removed from panier.');
        }

        return $this->redirectToRoute('app_panier', ['user' => $user->getId()]);
    }
}
