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
    public function paymentProcess(EntityManagerInterface $em, Request $request): RedirectResponse
    {
        $user   = $this->getUser();
        $panier = $user->getPanier();

        $commande = new Commande();
        $commande->setUser($user);
        foreach ($panier->getProducts() as $product) {
            $commande->addProduct($product);
        }
        $em->persist($commande);
        $em->flush();

        foreach ($panier->getProducts() as $product) {
            $newQty = max(0, $product->getQuantity() - 1);
            $product->setQuantity($newQty);
            $em->persist($product);
        }
        $em->flush();

        foreach ($panier->getProducts() as $product) {
            $panier->removeProduct($product);
        }
        $em->persist($panier);
        $em->flush();

        $user->addCommande($commande);
        $em->persist($user);
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
