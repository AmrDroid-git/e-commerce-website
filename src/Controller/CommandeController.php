<?php

namespace App\Controller;

use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted; // Ou Sensio si Symfony < 6.2

class CommandeController extends AbstractController
{
    #[Route('/commandes', name: 'app_commandes')]
    public function mesCommandes(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos commandes.');
        }

        $commandes = $user->getCommandes();

        return $this->render('commandes.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/commande/delete/{id}', name: 'commande_delete')]
    #[IsGranted('ROLE_USER')]
    public function delete(Commande $commande, EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();

        if ($commande->getUser() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer cette commande.');
            return $this->redirectToRoute('app_commandes');
        }
        $products = $commande->getProducts();
        foreach ($products as $product) {
            $product->setQuantity($product->getQuantity()+1);
        }
        $em->remove($commande);
        $em->flush();

        $this->addFlash('success', 'Commande annulée avec succès.');
        return $this->redirectToRoute('app_commandes');
    }
}

