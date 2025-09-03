<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RoleController extends AbstractController
{
    #[Route('/manageusers', name: 'app_manage_users', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $currentUser = $this->getUser();
        $currentId = $currentUser ? $currentUser->getId() : 0;

        $usersOnly = $em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :roleUser')
            ->andWhere('u.roles NOT LIKE :roleAdmin')
            ->setParameter('roleUser', '%ROLE_USER%')
            ->setParameter('roleAdmin', '%ROLE_ADMIN%')
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();

        $admins = $em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :roleAdmin')
            ->andWhere('u.id != :currentId')
            ->setParameter('roleAdmin', '%ROLE_ADMIN%')
            ->setParameter('currentId', $currentId)
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('manage/manage_users.html.twig', [
            'usersOnly' => $usersOnly,
            'admins' => $admins,
            'usersForDelete' => $usersOnly,
        ]);
    }

    #[Route('/manageusers/promote', name: 'app_manage_promote', methods: ['POST'])]
    public function promote(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('manage_users_promote', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid security token.');
            return $this->redirectToRoute('app_manage_users');
        }

        $userId = (int) $request->request->get('promote_user_id');
        $user = $userId > 0 ? $em->getRepository(User::class)->find($userId) : null;

        if (!$user) {
            $this->addFlash('danger', 'User not found.');
            return $this->redirectToRoute('app_manage_users');
        }

        $user->setRoles(['ROLE_ADMIN']);
        $em->flush();
        $this->addFlash('success', 'User promoted to admin.');

        return $this->redirectToRoute('app_manage_users');
    }

    #[Route('/manageusers/demote', name: 'app_manage_demote', methods: ['POST'])]
    public function demote(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('manage_users_demote', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid security token.');
            return $this->redirectToRoute('app_manage_users');
        }

        $adminId = (int) $request->request->get('demote_admin_id');
        $user = $adminId > 0 ? $em->getRepository(User::class)->find($adminId) : null;

        if (!$user || $user === $this->getUser()) {
            $this->addFlash('danger', 'Admin not found or cannot be modified.');
            return $this->redirectToRoute('app_manage_users');
        }

        $user->setRoles(['ROLE_USER']);
        $em->flush();
        $this->addFlash('success', 'Admin demoted to user.');

        return $this->redirectToRoute('app_manage_users');
    }

    #[Route('/manageusers/delete', name: 'app_manage_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('manage_users_delete', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid security token.');
            return $this->redirectToRoute('app_manage_users');
        }

        $userId = (int) $request->request->get('delete_user_id');
        $user = $userId > 0 ? $em->getRepository(User::class)->find($userId) : null;

        if (!$user || $user === $this->getUser()) {
            $this->addFlash('danger', 'User not found or cannot be deleted.');
            return $this->redirectToRoute('app_manage_users');
        }

        $em->remove($user);
        $em->flush();
        $this->addFlash('success', 'User deleted successfully.');

        return $this->redirectToRoute('app_manage_users');
    }
}

# backdated-commit: 2025-09-03 00:00:00
