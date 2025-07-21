<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
}
