<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RoleController extends AbstractController
{
    #[Route('/manageusers', name: 'app_manage_users', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $currentUser = $this->getUser();
        $currentId   = $currentUser ? $currentUser->getId() : 0;

        // 1) Find all pure ROLE_USER users (unchanged)
        $usersOnly = $em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :roleUser')
            ->andWhere('u.roles NOT LIKE :roleAdmin')
            ->setParameter('roleUser', '%ROLE_USER%')
            ->setParameter('roleAdmin', '%ROLE_ADMIN%')
            ->getQuery()
            ->getResult();

        // 2) Find all admins, but exclude the current user
        $admins = $em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :roleAdmin')
            ->andWhere('u.id != :currentId')
            ->setParameter('roleAdmin', '%ROLE_ADMIN%')
            ->setParameter('currentId', $currentId)
            ->getQuery()
            ->getResult();

        // 3) For deletion, we still use $usersOnly
        $usersForDelete = $usersOnly;

        return $this->render('manage/manage_users.html.twig', [
            'usersOnly'      => $usersOnly,
            'admins'         => $admins,
            'usersForDelete' => $usersForDelete,
        ]);
    }

    #[Route('/manageusers/promote', name: 'app_manage_promote', methods: ['POST'])]
    public function promote(Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->request->get('promote_user_id');
        if ($userId) {
            $user = $em->getRepository(User::class)->find($userId);
            if ($user) {
                // Overwrite roles with exactly ROLE_ADMIN (removes ROLE_USER)
                $user->setRoles(['ROLE_ADMIN']);
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'User has been promoted to ADMIN (ROLE_USER removed).');
            } else {
                $this->addFlash('error', 'User not found.');
            }
        }

        return $this->redirectToRoute('app_manage_users');
    }

    #[Route('/manageusers/demote', name: 'app_manage_demote', methods: ['POST'])]
    public function demote(Request $request, EntityManagerInterface $em): Response
    {
        $adminId = $request->request->get('demote_admin_id');
        if ($adminId) {
            $user = $em->getRepository(User::class)->find($adminId);
            if ($user) {
                // Overwrite roles with exactly ROLE_USER
                $user->setRoles(['ROLE_USER']);
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'Admin has been demoted to USER.');
            } else {
                $this->addFlash('error', 'Admin not found.');
            }
        }

        return $this->redirectToRoute('app_manage_users');
    }

    #[Route('/manageusers/delete', name: 'app_manage_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->request->get('delete_user_id');
        if ($userId) {
            $user = $em->getRepository(User::class)->find($userId);
            if ($user) {
                $em->remove($user);
                $em->flush();
                $this->addFlash('success', 'User has been deleted successfully.');
            } else {
                $this->addFlash('error', 'User not found.');
            }
        }

        return $this->redirectToRoute('app_manage_users');
    }
}
