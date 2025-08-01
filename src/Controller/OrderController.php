<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\PaymentTypeRepository;
use App\Repository\ProductRepository;
use App\Service\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    #[Route('/order/create', name: 'order_create')]
    public function create(
        SessionInterface $session,
        ProductRepository $productRepository,
        PaymentTypeRepository $paymentTypeRepository): Response
    {
        $cart = $session->get('cart', []);

        if (empty($cart)) {
            flash()->warning('Ваш кошик порожнiй', (array)'Warning');
            return $this->redirectToRoute('app_product');
        }

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

            $productCategories = $product->getCategories();

            $categoriesForProduct = [];
            foreach ($productCategories as $category) {
                $categoriesForProduct[] = [
                    'id' => $category->getId(),
                    'name' => $category->getCategoryName()
                ];
            }

            $selectedItems[] = [
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $subtotal,
                'image' => $imagePath,
                'categories' => $categoriesForProduct
            ];
        }

        return $this->render('order/create.html.twig', [
            'selectedItems' => $selectedItems,
            'total' => $total,
            'paymentTypes' => $paymentTypes
        ]);
    }

    #[Route('/checkout', name: 'app_checkout', methods: ['POST'])]
    public function checkout(
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $session,
        PaymentTypeRepository $paymentTypeRepository,
        ProductRepository $productRepository,
        TelegramService $telegramService
    ): Response {
        $cart = $session->get('cart', []);
        $user = $this->getUser();

        if (empty($cart)) {
            flash()->error('Кошик порожній', (array)'Error');

            return $this->redirectToRoute('app_cart');
        }

        $data = $request->request->all();

        $order = new Order();
        $order->setClientName($data['clientName'] ?? '');
        $order->setClientEmail($data['clientEmail'] ?? '');
        $order->setClientPhone($data['clientPhone'] ?? '');
        $order->setRegion($data['region'] ?? '');
        $order->setCity($data['city'] ?? '');
        $order->setDepartment($data['department'] ?? '');
        $order->setComment($data['comment'] ?? null);
        $order->setStatus(Order::STATUS_PENDING);
        $order->setCreatedAt(new \DateTimeImmutable());

        if ($user) {
            $order->setUserId($user->getId());
        }

        if (isset($data['paymentTypeId'])) {
            $paymentType = $paymentTypeRepository->find($data['paymentTypeId']);

            if ($paymentType) {
                $order->setPaymentType($paymentType);
            }
        }

        $totalPrice = 0;

        $itemDetails = json_decode($data['itemDetails'] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
        $index = 0;

        foreach ($cart as $productId => $item) {
            $product = $productRepository->find($productId);
            if (!$product) continue;

            $quantity = $item['quantity'] ?? 1;
            $details = $itemDetails[$index] ?? [];

            for ($i = 0; $i < $quantity; $i++) {
                $orderItem = new OrderItem();
                $orderItem->setOrder($order);
                $orderItem->setProduct($product);
                $orderItem->setQuantity(1);

                $detail = $details[$i] ?? [];

                if (!empty($detail['photo'])) {
                    $data = explode(',', $detail['photo']);
                    if (isset($data[1])) {
                        $decoded = base64_decode($data[1]);
                        $filename = uniqid('', true) . '.png';
                        $filepath = '/uploads/orderImages/' . $filename;

                        file_put_contents($this->getParameter('kernel.project_dir') . '/public/' . $filepath, $decoded);

                        $orderItem->setPhotoPath($filepath);
                    }
                }

                if (!empty($detail['message'])) {
                    $orderItem->setMessageText($detail['message']);
                }

                if (!empty($detail['categoryId'])) {
                    $category = $em->getReference(Category::class, $detail['categoryId']);
                    $orderItem->setCategory($category);
                }

                $order->getOrderItems()->add($orderItem);
            }

            $totalPrice += $product->getPrice() * $quantity;
            $index++;
        }

        $order->setTotalPrice($totalPrice);

        $em->persist($order);
        $em->flush();

        // Telegram
        $message = "<b>Нове замовлення</b>\n\n";
        $message .= "👤 <b>Ім'я:</b> " . $order->getClientName() . "\n";
        $message .= "📧 <b>Email:</b> " . $order->getClientEmail() . "\n";
        $message .= "📞 <b>Телефон:</b> " . $order->getClientPhone() . "\n";
        $message .= "📍 <b>Область:</b> " . $order->getRegion() . "\n";
        $message .= "🏙️ <b>Місто:</b> " . $order->getCity() . "\n";
        $message .= "🏤 <b>Відділення:</b> " . $order->getDepartment() . "\n";

        if ($order->getComment()) {
            $message .= "💬 <b>Коментар:</b> " . $order->getComment() . "\n\n";
        }

        $message .= "💵 <b>Сума:</b> " . $order->getTotalPrice() . " грн";

        $telegramService->sendMessage($message);

        foreach ($order->getOrderItems() as $item) {
            if ($item->getPhotoPath()) {
                $fullPath = $this->getParameter('kernel.project_dir') . '/public' . $item->getPhotoPath();

                if (file_exists($fullPath)) {
                    $caption = $item->getProduct()->getName();

                    if ($item->getMessageText()) {
                        $caption .= "\n✏️ Текст: " . $item->getMessageText();
                    }

                    if ($item->getCategory()->getCategoryName()) {
                        $caption .= "\n✏️ Смак: " . $item->getCategory()->getCategoryName();
                    }

                    $telegramService->sendDocument($fullPath, $caption);
                }
            }
        }

        $session->remove('cart');
        flash()->success('Ваше замовлення успiшно створено', (array)'Success');

        return $this->redirectToRoute('app_home');
    }
}
