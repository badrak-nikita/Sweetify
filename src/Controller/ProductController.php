<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\Filters;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    private Filters $filters;
    private EntityManagerInterface $em;

    public function __construct(Filters $filters, EntityManagerInterface $em)
    {
        $this->filters = $filters;
        $this->em = $em;
    }

    #[Route('/products', name: 'app_product')]
    public function index(Request $request, CategoryRepository $categoryRepository): Response
    {
        $searchTerm = trim((string)$request->query->get('search', ''));
        $categoryIds = $request->query->all('category');

        $products = $this->filters->getFilteredProducts($this->em, $searchTerm, $categoryIds);
        $categories = $categoryRepository->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    #[Route('/cart/add', name: 'cart_add', methods: ['POST'])]
    public function addToCart(Request $request, SessionInterface $session, ProductRepository $productRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $productId = (int)($data['productId'] ?? 0);
        $quantity = (int)($data['quantity'] ?? 1);

        $product = $productRepository->find($productId);
        if (!$product) {
            return new JsonResponse(['message' => 'Товар не знайдено'], 404);
        }

        $cart = $session->get('cart', []);

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'quantity' => $quantity
            ];
        }

        $session->set('cart', $cart);

        return new JsonResponse(['message' => 'Товар додано до кошика']);
    }

    #[Route('/cart/count', name: 'cart_count')]
    public function count(SessionInterface $session): JsonResponse
    {
        $cart = $session->get('cart', []);
        $count = array_sum(array_column($cart, 'quantity'));

        return new JsonResponse(['count' => $count]);
    }

    #[Route('/cart/items', name: 'cart_items')]
    public function items(SessionInterface $session, EntityManagerInterface $em): JsonResponse
    {
        $cart = $session->get('cart', []);
        $items = [];

        foreach ($cart as $id => $item) {
            $product = $em->getRepository(Product::class)->find($id);

            $imagePath = null;
            if ($product && count($product->getImages()) > 0) {
                $imagePath = $product->getImages()[0]->getImagePath();
            }

            $items[] = [
                'id' => $id,
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'image' => $imagePath ?? '/images/default.png'
            ];
        }

        return new JsonResponse(['items' => $items]);
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    public function remove(int $id, SessionInterface $session): JsonResponse
    {
        $cart = $session->get('cart', []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
            $session->set('cart', $cart);
        }

        return new JsonResponse(['success' => true]);
    }
}
