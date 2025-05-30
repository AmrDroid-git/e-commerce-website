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
    public function processPayment(EntityManagerInterface $em, Request $request): Response
    {
        $user   = $this->getUser();
        $panier = $user->getPanier();

        // 0. Stock check
        foreach ($panier->getProducts() as $product) {
            if (($product->getQuantity() ?? 0) <= 0) {
                $this->addFlash('error', 'Sorry, one of the products is out of stock.');

                // Build a preview order again for the template
                $previewOrder = new Commande();
                $previewOrder->setUser($user);
                foreach ($panier->getProducts() as $p) {
                    $previewOrder->addProduct($p);
                }

                return $this->render('checkout/index.html.twig', [
                    'commande'   => $previewOrder,
                    'totalPrice' => $previewOrder->getTotalPrice(),
                    'panier'     => $panier,
                ]);
            }
        }

        // 1. Everything in stock → proceed
        $commande = new Commande();
        $commande->setUser($user);
        foreach ($panier->getProducts()->toArray() as $product) {
            $commande->addProduct($product);
            $currentQty = $product->getQuantity() ?? 0;
            $product->setQuantity(max(0, $currentQty - 1));
            $em->persist($product);
            $panier->removeProduct($product);
        }

        // 2. Persist & flush
        $em->persist($commande);
        $em->persist($panier);
        $em->flush();

        $this->addFlash('success', 'Your order has been placed!');
        return $this->redirectToRoute('app_dashboard');
    }

}
