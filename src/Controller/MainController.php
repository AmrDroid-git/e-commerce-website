<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Service\ProductDisplayService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(CategoryRepository $categoryRepository, ProductDisplayService $productDisplay): Response
    {
        $featuredProducts = $productDisplay->getTopRatedProducts(3);

        return $this->render('main/index.html.twig', [
            'productsWithRating' => $productDisplay->decorateProducts($featuredProducts),
            'categories' => $categoryRepository->findAll(),
            'topProducts' => $featuredProducts,
        ]);
    }
}

# backdated-commit: 2025-08-27 00:00:00
