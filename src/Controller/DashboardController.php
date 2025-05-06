<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        $products = [
            ['name' => 'Burger', 'description' => 'Delicious cheeseburger', 'image' => '/images/burger.jpg'],
            ['name' => 'Pizza', 'description' => 'Pepperoni pizza slice', 'image' => '/images/pizza.jpg'],
            ['name' => 'Pasta', 'description' => 'Creamy Alfredo pasta', 'image' => '/images/pasta.jpg'],
        ];

        return $this->render('dashboard/index.html.twig', [
            'products' => $products,
        ]);
    }

}
