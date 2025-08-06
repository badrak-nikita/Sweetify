<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class GoogleAuthController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry->getClient('google')->redirect([
            'profile', 'email'
        ]);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheck(
        Request $request,
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher,
        TokenStorageInterface $tokenStorage
    ) {
        $client = $clientRegistry->getClient('google');
        $googleUser = $client->fetchUser();

        /** @var GoogleUser $googleUser */
        $email = $googleUser->getEmail();
        $name = $googleUser->getName();

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setName($name);
            $user->setRoles(['ROLE_USER']);
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setPassword(bin2hex(random_bytes(16)));
            $user->setIsGoogleUser(true);

            $em->persist($user);
            $em->flush();
        }

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage->setToken($token);

        $request->getSession()->set('_security_main', serialize($token));

        $eventDispatcher->dispatch(
            new InteractiveLoginEvent($request, $token),
            'security.interactive_login'
        );

        return $this->redirectToRoute('app_home');
    }
}
