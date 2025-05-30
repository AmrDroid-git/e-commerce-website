<?php
namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Panier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class CheckoutController extends AbstractController
{
    #[Route('/payment', name: 'payment_process', methods: ['POST'])]
    public function paymentProcess(EntityManagerInterface $em): RedirectResponse
    {
        $user   = $this->getUser();
        $panier = $user->getPanier();

        // Copy products to avoid modifying the collection as we loop
        $products = $panier->getProducts()->toArray();

        $commande = new Commande();
        $commande->setUser($user);

        foreach ($products as $product) {
            // 1. Add to order
            $commande->addProduct($product);

            // 2. Decrement stock
            $qty = $product->getQuantity() ?? 0;
            $product->setQuantity(max(0, $qty - 1));
            $em->persist($product);

            // 3. Remove from cart
            $panier->removeProduct($product);
        }

        // Persist all at once
        $em->persist($commande);
        $em->persist($panier);
        $em->flush();

        return $this->redirectToRoute('app_dashboard');
    }


    #[Route('/checkout', name: 'app_checkout')]
    public function checkout(): Response
    {
        $user = $this->getUser();

        if (!$user || !$user->getPanier()) {
            return $this->redirectToRoute('product');
        }

        $panier = $user->getPanier();

        $commande = new Commande();
        $commande->setUser($user);
        foreach ($panier->getProducts() as $product) {
            $commande->addProduct($product);
        }

        $totalPrice = $commande->getTotalPrice();

        return $this->render('checkout/index.html.twig', [
            'commande'   => $commande,
            'totalPrice' => $totalPrice
        ]);
    }
}
