<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Category;                        // [ADDED]
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;     // [ADDED]
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/product', name: 'product')]
    public function index(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user       = $security->getUser();
        $searchTerm = trim((string) $request->query->get('search', ''));    // [ADDED]
        $categoryId = $request->query->getInt('category', 0);               // [ADDED]

        $categories = $entityManager->getRepository(Category::class)->findAll();    // [ADDED]

        $qb = $entityManager->getRepository(Product::class)->createQueryBuilder('p'); // [CHANGED]

        if ($searchTerm !== '') {
            $qb->andWhere('LOWER(p.name) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($searchTerm) . '%');
        }

        if ($categoryId > 0) {                                                     // [ADDED]
            $category = $entityManager->getRepository(Category::class)->find($categoryId); // [ADDED]
            if ($category) {                                                       // [ADDED]
                $qb->andWhere('p.category = :catName')                             // [ADDED]
                ->setParameter('catName', $category->getName());                // [ADDED]
            }                                                                       // [ADDED]
        }                                                                           // [ADDED]

        $qb->orderBy('p.name', 'ASC');                                              // [ADDED]

        $products = $qb->getQuery()->getResult();                                    // [CHANGED]

        return $this->render('product/index.html.twig', [
            'products'       => $products,
            'categories'     => $categories,       // [ADDED]
            'user'           => $user,
            'currentSearch'   => $searchTerm,       // [ADDED]
            'currentCategory' => $categoryId,       // [ADDED]
        ]);
    }

    #[Route('/product/{id}', name: 'product_show', requirements: ['id' => '\d+'])]
    public function show(int $id, EntityManagerInterface $em, Security $security): Response
    {
        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $user = $security->getUser();

        $ratings = $product->getRatings();
        $averageRating = 0;
        if (count($ratings) > 0) {
            $sum           = array_sum(array_map(fn($r) => $r->getValue(), $ratings->toArray()));
            $averageRating = round($sum / count($ratings), 2);
        }

        return $this->render('product/show.html.twig', [
            'product'        => $product,
            'comments'       => $product->getComments(),
            'ratings'        => $ratings,
            'average_rating' => $averageRating,
            'user'           => $user,
        ]);
    }
}
