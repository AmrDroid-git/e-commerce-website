<?php

namespace App\Controller;

use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout', methods: ['GET'])]
    public function checkout(OrderService $orderService): Response
    {
        $user = $this->getUser();
        if (!$user || !$user->getPanier()) {
            return $this->redirectToRoute('app_login');
        }

        $panier = $user->getPanier();
        $commande = $orderService->createPreviewOrder($panier);

        return $this->render('checkout/index.html.twig', [
            'commande' => $commande,
            'totalPrice' => $commande->getPrice(),
            'panier' => $panier,
        ]);
    }

    #[Route('/checkout/direct_payment', name: 'app_direct_payment', methods: ['GET', 'POST'])]
    public function directPayment(OrderService $orderService): Response
    {
        $user = $this->getUser();
        if (!$user || !$user->getPanier()) {
            return $this->redirectToRoute('app_login');
        }

        $panier = $user->getPanier();
        if ($panier->getProducts()->isEmpty()) {
            $this->addFlash('warning', 'Your cart is empty.');
            return $this->redirectToRoute('product');
        }

        $commande = $orderService->createPreviewOrder($panier);

        return $this->render('checkout/direct_payment.html.twig', [
            'commande' => $commande,
            'totalPrice' => $commande->getPrice(),
            'panier' => $panier,
        ]);
    }

    #[Route('/checkout/process_direct', name: 'payment_process', methods: ['POST'])]
    public function processPayment(Request $request, OrderService $orderService): Response
    {
        $user = $this->getUser();
        if (!$user || !$user->getPanier()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('direct_payment', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_direct_payment');
        }

        $panier = $user->getPanier();
        $errors = $orderService->validateCartStock($panier);
        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }

            return $this->render('checkout/direct_payment.html.twig', [
                'commande' => $orderService->createPreviewOrder($panier),
                'totalPrice' => $orderService->createPreviewOrder($panier)->getPrice(),
                'panier' => $panier,
            ]);
        }

        $orderService->placeOrderFromCart($panier);
        $this->addFlash('success', 'Your order has been placed successfully.');

        return $this->redirectToRoute('app_dashboard');
    }
}

# backdated-commit: 2025-08-21 00:00:00
