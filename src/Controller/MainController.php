<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\User;

final class MainController extends AbstractController
{
    #[Route('/main', name: 'app_main')]
    public function index(UserInterface $user): Response
    {
        // Неожиданно, но кастинг классов
        if (!$user instanceof User){
            throw $this->createAccessDeniedException();
        }

        return $this->render('main/index.html.twig', [
            'username' => $user->getName() ?? '',
            'email' => $user->getEmail() ?? '',
            'phone' => $user->getPhone() ?? '-',
            'roles' => $user->getRoles() ?? [],
        ]);
    }
}
