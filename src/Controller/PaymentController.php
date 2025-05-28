<?php
// src/Controller/PaymentController.php
namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Panier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class PaymentController extends AbstractController
{
    #[Route('/payment-process', name: 'payment_process', methods: ['POST'])]
    public function processPayment(EntityManagerInterface $em, Request $request): RedirectResponse
    {
        $cardNumber = $request->request->get('card_number');
        $secretKey = $request->request->get('secret_key');

        $paymentSuccessful = true;

        if ($paymentSuccessful) {
            $user = $this->getUser();
            $panier = $user->getPanier();

            $commande = new Commande();
            $commande->setUser($user);

            foreach ($panier->getProducts() as $product) {
                $commande->addProduct($product);
            }

            $em->persist($commande);
            $em->flush();

            foreach ($panier->getProducts() as $product) {
                $panier->removeProduct($product);
            }

            $em->persist($panier);
            $em->flush();

            $user->addCommande($commande);
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Payment processed successfully!');

            return $this->redirectToRoute('app_dashboard');
        }

        $this->addFlash('error', 'Payment failed. Please try again.');

        return $this->redirectToRoute('app_checkout');
    }
}
