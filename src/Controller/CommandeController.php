<?php

namespace App\Controller;

use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CommandeController extends AbstractController
{
    #[Route('/commandes', name: 'app_commandes', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function mesCommandes(): Response
    {
        return $this->render('commandes.html.twig', [
            'commandes' => $this->getUser()->getCommandes(),
        ]);
    }

    #[Route('/commande/delete/{id}', name: 'commande_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Commande $commande, EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();

        if ($commande->getUser() !== $user) {
            $this->addFlash('danger', 'You cannot cancel this order.');
            return $this->redirectToRoute('app_commandes');
        }

        if (!$this->isCsrfTokenValid('delete_commande_' . $commande->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid security token.');
            return $this->redirectToRoute('app_commandes');
        }

        foreach ($commande->getProducts() as $product) {
            $product->setQuantity(($product->getQuantity() ?? 0) + 1);
        }

        $em->remove($commande);
        $em->flush();

        $this->addFlash('success', 'Order cancelled successfully.');
        return $this->redirectToRoute('app_commandes');
    }
}

# backdated-commit: 2025-08-22 00:00:00
