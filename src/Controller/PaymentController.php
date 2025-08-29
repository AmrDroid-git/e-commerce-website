<?php

namespace App\Controller;

use App\Repository\PanierRepository;
use App\Service\OrderService;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PaymentController extends AbstractController
{
    public function __construct(
        private readonly PanierRepository $panierRepository,
        private readonly Security $security,
        private readonly OrderService $orderService
    ) {
    }

    #[Route('/payment/stripe', name: 'app_payment', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            $this->addFlash('warning', 'You must be logged in to pay.');
            return $this->redirectToRoute('app_login');
        }

        $panier = $this->panierRepository->findOneBy(['user' => $user]);
        if (!$panier) {
            $this->addFlash('warning', 'Your cart is empty.');
            return $this->redirectToRoute('product');
        }

        $errors = $this->orderService->validateCartStock($panier);
        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->addFlash('danger', $error);
            }

            return $this->redirectToRoute('app_panier', ['user' => $user->getId()]);
        }

        Stripe::setApiKey((string) $this->getParameter('stripe_secret_key'));

        $lineItems = [];
        foreach ($panier->getProducts() as $product) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => (int) round(((float) $product->getPrice()) * 100),
                    'product_data' => [
                        'name' => $product->getName(),
                    ],
                ],
                'quantity' => 1,
            ];
        }

        $baseUrl = rtrim((string) $this->getParameter('app_base_url'), '/');
        $successUrl = $baseUrl . $this->generateUrl('app_payment_success');
        $cancelUrl = $baseUrl . $this->generateUrl('app_payment_cancel');

        try {
            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $cancelUrl,
                'client_reference_id' => (string) $user->getId(),
                'metadata' => [
                    'user_id' => (string) $user->getId(),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Stripe could not start the payment session.');
            return $this->redirectToRoute('app_panier', ['user' => $user->getId()]);
        }

        return new RedirectResponse((string) $checkoutSession->url);
    }

    #[Route('/payment/success', name: 'app_payment_success', methods: ['GET'])]
    public function success(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Please log in to confirm payment.');
            return $this->redirectToRoute('app_login');
        }

        $sessionId = (string) $request->query->get('session_id', '');
        if ($sessionId === '') {
            $this->addFlash('danger', 'Missing payment session.');
            return $this->redirectToRoute('product');
        }

        Stripe::setApiKey((string) $this->getParameter('stripe_secret_key'));

        try {
            $session = Session::retrieve($sessionId);
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Unable to verify the payment session.');
            return $this->redirectToRoute('product');
        }

        $metadataUserId = (string) ($session->metadata->user_id ?? '');
        if ($metadataUserId !== (string) $user->getId() || $session->payment_status !== 'paid') {
            $this->addFlash('danger', 'Payment could not be verified.');
            return $this->redirectToRoute('product');
        }

        $panier = $user->getPanier();
        if (!$panier) {
            $this->addFlash('warning', 'Your cart was already processed.');
            return $this->redirectToRoute('product');
        }

        try {
            $commande = $this->orderService->placeOrderFromCart($panier);
        } catch (\RuntimeException $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('app_panier', ['user' => $user->getId()]);
        }

        $this->addFlash('success', 'Payment confirmed and order saved.');

        return $this->render('payment/success.html.twig', [
            'session_id' => $sessionId,
            'commande' => $commande,
        ]);
    }

    #[Route('/payment/cancel', name: 'app_payment_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Payment was cancelled.');
        $user = $this->security->getUser();

        return $this->redirectToRoute('app_panier', [
            'user' => $user ? $user->getId() : 0,
        ]);
    }
}

# backdated-commit: 2025-08-29 00:00:00
