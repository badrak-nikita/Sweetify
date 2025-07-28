<?php

namespace App\Controller;

use App\Repository\PaymentTypeRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    #[Route('/order/create', name: 'order_create')]
    public function create(SessionInterface $session, ProductRepository $productRepository, PaymentTypeRepository $paymentTypeRepository): Response
    {
        $cart = $session->get('cart', []);
        $selectedItems = [];
        $total = 0;
        $paymentTypes = $paymentTypeRepository->findAll();

        foreach ($cart as $productId => $item) {
            $product = $productRepository->find($productId);
            if (!$product) continue;

            $imagePath = '/images/default.png';
            if (count($product->getImages()) > 0) {
                $imagePath = $product->getImages()[0]->getImagePath();
            }

            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;

            $selectedItems[] = [
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $subtotal,
                'image' => $imagePath
            ];
        }

        return $this->render('order/create.html.twig', [
            'selectedItems' => $selectedItems,
            'total' => $total,
            'paymentTypes' => $paymentTypes
        ]);
    }
}
