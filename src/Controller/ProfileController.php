<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/profile', name: 'app_profile')]
    public function index(OrderRepository $orderRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->getUser();

        $queryBuilder = $orderRepository->createQueryBuilder('o')
            ->andWhere('o.userId = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC');

        $page = $request->query->getInt('page', 1);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $page,
            5
        );

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'pagination' => $pagination,
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/profile/edit', name: 'app_profile_edit', methods: ['POST'])]
    public function editProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $user->setName($request->request->get('name'));
        $user->setPhone($request->request->get('phone'));
        $user->setTelegram($request->request->get('telegram'));
        $user->setInstagram($request->request->get('instagram'));
        $user->setUpdatedAt(new \DateTimeImmutable('now'));

        $file = $request->files->get('avatar');

        if ($request->request->get('remove_avatar') == '1') {
            $user->setImagePath(null);
        }

        if ($file) {
            $filename = uniqid('', true) . '.' . $file->guessExtension();
            $file->move($this->getParameter('profileImage_directory'), $filename);

            $user->setImagePath('/uploads/profileImages/' . $filename);
        }

        $entityManager->flush();

        flash()->success('Ваш профiль успiшно оновлено', (array)'Success');

        return $this->redirectToRoute('app_profile');
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/profile/change_password', name: 'app_profile_change_password', methods: ['POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $currentPassword = $request->request->get('current_password');
        $newPassword = $request->request->get('new_password');
        $confirmPassword = $request->request->get('confirm_password');

        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            flash()->error('Неправильний поточний пароль', (array)'Error');

            return $this->redirectToRoute('app_profile');
        }

        if ($newPassword !== $confirmPassword) {
            flash()->error('Паролi не співпадають', (array)'Error');

            return $this->redirectToRoute('app_profile');
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));

        $entityManager->flush();

        flash()->success('Пароль успішно змінено', (array)'Success');

        return $this->redirectToRoute('app_profile');
    }
}
