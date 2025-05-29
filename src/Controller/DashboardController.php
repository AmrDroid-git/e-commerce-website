<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Rating;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Product;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $em
    ) {}

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->security->getUser();
        $favorites = $user ? $user->getFavorites() : [];
        $averageRating = null;

        $productRepo = $this->em->getRepository(Product::class);
        $commentRepo = $this->em->getRepository(Comment::class);

        if ($user) {
            $averageRating = $em->getRepository(Rating::class)->findAverageRatingByUser($user);
        }

        $qb = $this->em
            ->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.ratings', 'r')
            ->addSelect('AVG(r.value) AS avgRating')
            ->groupBy('p.id')
            ->orderBy('avgRating', 'DESC')
            ->setMaxResults(3);

        /** @var array<int, array{0: Product, avgRating: float|null}> $rows */
        $rows = $qb->getQuery()->getResult();
        $topProducts = array_map(fn($row) => $row[0], $rows);

        $recentComments = $commentRepo->findMostRecent(5);


        return $this->render('dashboard/index.html.twig', [
            'topProducts' => $topProducts,
            'favorites' => $favorites,
            'recent_comments' => $recentComments,
            "averageRating" => $averageRating,
        ]);
    }
}
