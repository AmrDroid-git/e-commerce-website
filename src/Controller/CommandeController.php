<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
        // Vérifie si la commande appartient à l'utilisateur
        if ($commande->getUser() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer cette commande.');
            return $this->redirectToRoute('app_commandes');
        }
        $em->remove($commande);
        $em->flush();
        $this->addFlash('success', 'Commande annulée avec succès.');
        return $this->redirectToRoute('app_commandes');
    }
}
