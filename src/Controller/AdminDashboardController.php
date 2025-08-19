<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Entity\Commande;                       // ← add this
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(EntityManagerInterface $entityManager,CommandeRepository $commandeRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $userCount = $entityManager->getRepository(User::class)->count([]);

        $latestUsers = $entityManager
            ->getRepository(User::class)
            ->findBy([], ['id' => 'DESC'], 5);

        $productCount = $entityManager->getRepository(Product::class)->count([]);

        $totalSalesDql = $entityManager->createQuery(
            'SELECT SUM(c.price) FROM App\Entity\Commande c'
        );
        $totalSales = $totalSalesDql->getSingleScalarResult();
        $totalSales = $totalSales !== null ? (float) $totalSales : 0.0;

        $commandeCount = $entityManager->getRepository(Commande::class)->count([]);

        $qb = $entityManager
            ->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.ratings', 'r')
            ->addSelect('AVG(r.value) AS avgRating')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('p.id')
            ->orderBy('avgRating', 'DESC')
            ->setMaxResults(3);
        $rows = $qb->getQuery()->getResult();
        $topProducts = array_map(fn($row) => $row[0], $rows);

        return $this->render('admin_dashboard/index.html.twig', [
            'userCount'     => $userCount,
            'latestUsers'   => $latestUsers,
            'productCount'  => $productCount,
            'totalSales'    => $totalSales,
            'commandeCount' => $commandeCount,
            'topProducts'   => $topProducts,
            'orders'        => $commandeRepo->findBy([], ['dateCommande' => 'DESC'])
        ]);
    }
}

# backdated-commit: 2025-08-19 00:00:00
