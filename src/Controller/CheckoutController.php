<?php

namespace App\Controller;

use App\Entity\Commande;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout', methods: ['GET'])]
    public function checkout(): Response
    {
        $user = $this->getUser();
        if (!$user || !$user->getPanier()) {
            return $this->redirectToRoute('product');
        }

        $panier = $user->getPanier();
        $commande = new Commande();
        $commande->setUser($user);
        foreach ($panier->getProducts() as $p) {
            $commande->addProduct($p);
        }

        return $this->render('checkout/index.html.twig', [
            'commande'   => $commande,
            'totalPrice' => $commande->getTotalPrice(),
            'panier'     => $panier,
        ]);
    }

    #[Route('/checkout', name: 'payment_process', methods: ['POST'])]
    public function processPayment(EntityManagerInterface $em, Request $request): RedirectResponse
    {

        $user   = $this->getUser();
        $panier = $user->getPanier();

        $commande = new Commande();
        $commande->setUser($user);

        foreach ($panier->getProducts()->toArray() as $product) {
            // 1. Add to order
            $commande->addProduct($product);

            // 2. Decrement stock
            $currentQty = $product->getQuantity() ?? 0;
            $product->setQuantity(max(0, $currentQty - 1));
            $em->persist($product);

            // 3. Remove from cart
            $panier->removeProduct($product);
        }

        // Persist order and updated cart
        $em->persist($commande);
        $em->persist($panier);
        $em->flush();

        $this->addFlash('success', 'Your order has been placed!');

        return $this->redirectToRoute('app_dashboard');
    }
}
