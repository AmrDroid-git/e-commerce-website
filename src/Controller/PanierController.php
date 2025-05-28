<?php

namespace App\Controller;

use App\Entity\User;
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
        $user = $security->getUser();
        $panier = $repository->findOneBy(['user' => $user]);
        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
            'user' => $user
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
        $user = $security->getUser();
        $panier = $panierRepository->findOneBy(['user' => $user]);
        $product = $productRepository->find($productId);

        if ($panier && $product) {
            $panier->addProduct($product);
            $entityManager->persist($panier);
            $entityManager->flush();
            $this->addFlash('success', 'Product added to panier successfully.');
        } else {
            $this->addFlash('error', 'Failed to add product to panier.');
        }

        return $this->redirectToRoute('app_panier', ['user' => $user->getId()]);
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
        $user = $security->getUser();
        $panier = $panierRepository->findOneBy(['user' => $user]);
        $product = $productRepository->find($productId);
        if ($panier && $product) {
            $panier->removeProduct($product);
            $entityManager->persist($panier);
            $entityManager->flush();
            $this->addFlash('success', 'Product removed from panier.');
        }
        return $this->redirectToRoute('app_panier', ['user' => $user->getId()]);
    }
}
