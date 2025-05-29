<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Panier;
use App\Entity\Category; // Assuming you have a Category entity
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/product', name: 'product')]
    public function index(EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();
        $products = $entityManager->getRepository(Product::class)->findAll();
        $categories = $entityManager->getRepository(Category::class)->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'user' => $user
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

        // Optional: Calculate average rating
        $ratings = $product->getRatings();
        $averageRating = 0;
        if (count($ratings) > 0) {
            $sum = array_sum(array_map(fn($r) => $r->getValue(), $ratings->toArray()));
            $averageRating = round($sum / count($ratings), 2);
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'comments' => $product->getComments(),
            'ratings' => $ratings,
            'average_rating' => $averageRating,
            'user' => $user,
        ]);
    }

}
