<?php
namespace App\Controller;

use App\Entity\Commande;
use App\Repository\PanierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class PaymentController extends AbstractController
{
    public function __construct(
        private PanierRepository $panierRepository,
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    #[Route('/payment/stripe', name: 'app_payment', methods: ['GET'])]
    public function index(): Response
    {
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $user = $this->security->getUser();
        if (! $user) {
            $this->addFlash('warning', 'Vous devez être connecté pour payer.');
            return $this->redirectToRoute('product');
        }

        $panier = $this->panierRepository->findOneBy(['user' => $user]);
        if (! $panier || $panier->getProducts()->isEmpty()) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('product');
        }

        $lineItems = [];
        foreach ($panier->getProducts() as $product) {
            $priceFloat  = (float) $product->getPrice();
            $amountCents = (int) round($priceFloat * 100);

            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => $amountCents,
                    'product_data' => [
                        'name' => $product->getName(),
                    ],
                ],
                'quantity' => 1,
            ];
        }

        $baseUrl    = rtrim($this->getParameter('app_base_url'), '/');
        $successUrl = $baseUrl . $this->generateUrl('app_payment_success');
        $cancelUrl  = $baseUrl . $this->generateUrl('app_payment_cancel');

        try {
            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items'           => $lineItems,
                'mode'                 => 'payment',
                'success_url'          => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'           => $cancelUrl,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur Stripe : ' . $e->getMessage());
            return $this->redirectToRoute('app_panier', ['user' => $user->getId()]);
        }

        return new RedirectResponse($checkoutSession->url);
    }

    #[Route('/payment/success', name: 'app_payment_success', methods: ['GET'])]
    public function success(Request $request): Response
    {
        $sessionId = $request->query->get('session_id');

        $user   = $this->security->getUser();
        if (! $user) {
            $this->addFlash('danger', 'Utilisateur non authentifié après paiement.');
            return $this->redirectToRoute('product');
        }

        $panier = $user->getPanier();
        if (! $panier || $panier->getProducts()->isEmpty()) {
            $this->addFlash('warning', 'Le panier était vide ou déjà traité.');
            return $this->redirectToRoute('product');
        }

        $commande = new Commande();
        $commande->setUser($user);
        foreach ($panier->getProducts()->toArray() as $product) {
            $commande->addProduct($product);
            $currentQty = $product->getQuantity() ?? 0;
            $product->setQuantity(max(0, $currentQty - 1));
            $this->entityManager->persist($product);
            $panier->removeProduct($product);
        }

        $this->entityManager->persist($commande);
        $this->entityManager->persist($panier);
        $this->entityManager->flush();

        $this->addFlash('success', 'Paiement confirmé ! Votre commande a été enregistrée.');

        return $this->render('payment/success.html.twig', [
            'session_id' => $sessionId,
        ]);
    }

    #[Route('/payment/cancel', name: 'app_payment_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Le paiement a été annulé.');
        $user = $this->security->getUser();

        return $this->redirectToRoute('app_panier', [
            'user' => $user ? $user->getId() : null,
        ]);
    }
}
