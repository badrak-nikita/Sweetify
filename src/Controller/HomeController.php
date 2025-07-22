<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->render('index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/404', name: 'app_error_page')]
    public function errorPage(): Response
    {
        return $this->render('404.html.twig');
    }
}
