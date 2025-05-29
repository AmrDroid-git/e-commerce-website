<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AboutContactController extends AbstractController
{
    #[Route('/about', name: 'app_about')]
    public function index(): Response
    {
        return $this->render('about/index.html.twig', [
            'controller_name' => 'AboutContactController',
        ]);
    }

    #[Route('/contact', name: 'app_contact')]
    public function index1(): Response
    {
        return $this->render('contact/index.html.twig', [
            'controller_name' => 'AboutContactController',
        ]);
    }
}
