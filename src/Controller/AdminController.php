<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductImageRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_home')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(OrderRepository $orderRepository, UserRepository $userRepository): Response
    {
        $pendingOrdersCount = $orderRepository->count(['status' => Order::STATUS_PENDING]);
        $completedOrders = $orderRepository->findBy(['status' => Order::STATUS_COMPLETED]);
        $totalOrders = $orderRepository->count(['status' => Order::STATUS_COMPLETED]);
        $totalUsers = $userRepository->count([]);

        $totalEarned = array_reduce($completedOrders, static fn($sum, $order) => $sum + $order->getTotalPrice(), 0);

        return $this->render('adminPanel/index.html.twig', [
            'pendingOrdersCount' => $pendingOrdersCount,
            'totalUsers' => $totalUsers,
            'totalEarned' => $totalEarned,
            'totalOrders' => $totalOrders,
        ]);
    }

    #[Route('/admin/products', name: 'admin_products')]
    #[IsGranted('ROLE_ADMIN')]
    public function products(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->render('adminPanel/product/index.html.twig', [
            'products' => $products
        ]);
    }

    #[Route('/admin/products/create', name: 'admin_create_products', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createProduct(
        Request $request,
        EntityManagerInterface $em,
        CategoryRepository $categoryRepository,
        SluggerInterface $slugger
    ): Response {
        $product = new Product();

        if ($request->isMethod('POST')) {
            $product->setName($request->request->get('productName'));
            $product->setDescription($request->request->get('description'));
            $product->setPrice((float) $request->request->get('price'));
            $product->setWeight((int) $request->request->get('weight'));
            $product->setCompound($request->request->get('compound'));

            $categoryIds = $request->request->all('category');
            foreach ($categoryIds as $categoryId) {
                $category = $categoryRepository->find($categoryId);
                if ($category) {
                    $product->addCategory($category);
                }
            }

            $em->persist($product);
            $em->flush();

            $imageFiles = $request->files->get('images');
            if ($imageFiles && is_array($imageFiles)) {
                foreach ($imageFiles as $uploadedFile) {
                    if ($uploadedFile) {
                        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename . '-' . uniqid('', true) . '.' . $uploadedFile->guessExtension();

                        try {
                            $uploadedFile->move(
                                $this->getParameter('productImage_directory'),
                                $newFilename
                            );
                        } catch (FileException $e) {
                            flash()->error('Помилка при завантаженнi зображень', (array)'Error');
                        }

                        $productImage = new ProductImage();
                        $productImage->setProduct($product);
                        $productImage->setImagePath('/uploads/productImages/' . $newFilename);
                        $em->persist($productImage);
                    }
                }
            }

            $em->flush();

            return $this->redirectToRoute('admin_products');
        }

        $categories = $categoryRepository->findAll();

        return $this->render('adminPanel/product/create.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/admin/products/{id}/edit', name: 'admin_edit_product', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function editProduct(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        SluggerInterface $slugger
    ): Response {
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Товар не знайдено');
        }

        if ($request->isMethod('POST')) {
            $product->setName($request->request->get('productName'));
            $product->setDescription($request->request->get('description'));
            $product->setPrice((float) $request->request->get('price'));
            $product->setWeight((int) $request->request->get('weight'));
            $product->setCompound($request->request->get('compound'));

            foreach ($product->getCategories() as $oldCategory) {
                $product->removeCategory($oldCategory);
            }

            $categoryIds = $request->request->all('category');
            foreach ($categoryIds as $categoryId) {
                $category = $categoryRepository->find($categoryId);
                if ($category) {
                    $product->addCategory($category);
                }
            }

            $imageFiles = $request->files->get('images');
            if ($imageFiles && is_array($imageFiles)) {
                foreach ($imageFiles as $uploadedFile) {
                    if ($uploadedFile) {
                        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename . '-' . uniqid('', true) . '.' . $uploadedFile->guessExtension();

                        try {
                            $uploadedFile->move(
                                $this->getParameter('productImage_directory'),
                                $newFilename
                            );
                        } catch (FileException $e) {
                            flash()->error('Помилка при завантаженнi зображень', (array)'Error');
                        }

                        $productImage = new ProductImage();
                        $productImage->setProduct($product);
                        $productImage->setImagePath('/uploads/productImages/' . $newFilename);
                        $em->persist($productImage);
                    }
                }
            }

            $em->flush();

            return $this->redirectToRoute('admin_products');
        }

        $categories = $categoryRepository->findAll();

        return $this->render('adminPanel/product/edit.html.twig', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    #[Route('/admin/product-image/{id}/delete', name: 'admin_delete_product_image', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteProductImage(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ProductImageRepository $imageRepository
    ): Response {
        $image = $imageRepository->find($id);

        if (!$image) {
            throw $this->createNotFoundException('Зображення не знайдено');
        }

        if ($this->isCsrfTokenValid('delete_image_' . $image->getId(), $request->request->get('_token'))) {
            $filesystem = new Filesystem();
            $imagePath = $this->getParameter('kernel.project_dir') . '/public' . $image->getImagePath();

            if ($filesystem->exists($imagePath)) {
                $filesystem->remove($imagePath);
            }

            $em->remove($image);
            $em->flush();
        }

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/admin/products/{id}/delete', name: 'admin_delete_product', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteProduct(
        int $id,
        ProductRepository $productRepository,
        EntityManagerInterface $em
    ): RedirectResponse {
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Товар не знайдено');
        }

        $em->remove($product);
        $em->flush();

        return $this->redirectToRoute('admin_products');
    }

    #[Route('/admin/clients', name: 'admin_clients')]
    #[IsGranted('ROLE_ADMIN')]
    public function clients(UserRepository $userRepository): Response
    {
        $clients = $userRepository->findAll();

        return $this->render('adminPanel/client/index.html.twig', [
            'clients' => $clients
        ]);
    }
}
