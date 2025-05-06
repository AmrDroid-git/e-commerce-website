<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RoleController extends AbstractController
{
    #[Route('/role{id}', name: 'app_role')]
    public function updateRole(User $user, EntityManagerInterface $entityManager)
    {
        // Mise à jour des rôles de l'utilisateur
        $user->setRoles(['ROLE_ADMIN']); // Assigner le rôle ROLE_ADMIN

        // Sauvegarder les changements dans la base de données
        $entityManager->flush();

        return $this->redirectToRoute('user_list');
    }
}
