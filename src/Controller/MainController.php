<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $products): Response
    {
        $allProducts = $products->findAll();

        return $this->render('main/index.html.twig', [
            'products' => $allProducts,
        ]);
    }


}
