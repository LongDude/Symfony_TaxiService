<?php

namespace App\Controller;

use App\Entity\Driver;
use App\Form\DriverForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\User;

final class DriverController extends AbstractController
{
    #[Route('/driver', name: 'app_driver')]
    public function index(): Response
    {
        return $this->render('driver/index.html.twig', [
            'controller_name' => 'DriverController',
        ]);
    }

    #[Route('/editProfile/driver', name: 'app_driver_profile', methods: ['GET', 'POST'])]
    public function registration(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $userPasswordHasherInterface
    ) {
        $user = $this->getUser();
        $isAuthorized = false;
        if ($user instanceof User){
            $isAuthorized = true;
        }
        $hasDriver = $isAuthorized && $user->getDriver() !== null;

        $driver = $hasDriver ? $user->getDriver() : new Driver();
        $form = $this->createForm(
            DriverForm::class,
            $driver,
            [
                'user_name' => $isAuthorized ? $user->getName() : null,
                'user_phone' => $isAuthorized ? $user->getPhone() : null,
                'user_email' => $isAuthorized ? $user->getEmail() : null,
            ]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$isAuthorized) {
                $user = new User();
            }
            $user->setEmail($form->get('email')->getData());
            $user->setName($form->get('name')->getData());
            $user->setPhone($form->get('phone')->getData());
            if (!$isAuthorized) {
                $user->setPassword(
                    $userPasswordHasherInterface->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            }
            $em->persist($user);

            if (!$hasDriver){
                $driver->setUser($user);
                $user->addRole('ROLE_DRIVER');
            }
            $em->persist($driver);
            $em->flush();
            $this->addFlash('success', $hasDriver ? 'Обновлен профиль водителя' : 'Регистрация водителя завершена');
            return $this->redirectToRoute('app_main');
        }
        
        return $this->render('driver/driverForm.twig', [
            'form_title' => $hasDriver ? 'Редактирование водителя': 'Регистрация водителя',
            'form' => $form

        ]);

    }
}
