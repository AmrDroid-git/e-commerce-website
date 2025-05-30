<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    public function __construct(
    ) {}
    #[Route('/', name: 'app_home')]
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $products, CategoryRepository $category, EntityManagerInterface $em): Response
    {
        $allCategories = $category->findAll();
        $allProducts = $products->findAll();

        $productsWithRating = [];

        foreach ($allProducts as $product) {
            $ratings = $product->getRatings();
            $averageRating = null;

            if (count($ratings) > 0) {
                $sum = array_sum(array_map(fn($r) => $r->getValue(), $ratings->toArray()));
                $averageRating = round($sum / count($ratings), 1); // e.g., 4.3
            }

            $productsWithRating[] = [
                'product' => $product,
                'rating' => $averageRating,
            ];
        }

        $qb = $em->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.ratings', 'r')
            ->addSelect('AVG(r.value) AS avgRating')
            ->groupBy('p.id')
            ->orderBy('avgRating', 'DESC')
            ->setMaxResults(3);

        $rows = $qb->getQuery()->getResult();
        $topProducts = array_map(fn($row) => $row[0], $rows);

        usort($productsWithRating, function ($a, $b) {
            return $b['rating'] <=> $a['rating'];
        });
        $productsWithRating = array_slice($productsWithRating, 0, 3);

        return $this->render('main/index.html.twig', [
            'productsWithRating' => $productsWithRating,
            'categories' => $allCategories,
            'topProducts' => $topProducts
        ]);
    }



}
