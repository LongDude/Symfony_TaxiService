<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Fawno\FPDF\FawnoFPDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use App\Validators\OrderValidator;
use App\Entity\Order;
use App\Entity\Tariff;
use App\Entity\User;
use App\Service\OrderReportGenerator;
use Symfony\Component\Security\Http\Attribute\IsGranted;
final class OrderController extends AbstractController
{
    #[Route('/orders', name: 'app_order')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
    ): Response {

        $orderRepository = $em->getRepository(Order::class);
        $tariffRepository = $em->getRepository(Tariff::class);
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        [$filter, $err] = OrderValidator::validateFilter($_GET);
        $list = $orderRepository->getFilteredList($filter);
        $msg = '';
        $tariffs_list = $tariffRepository->findAll();


        foreach ($list as $k => $r) {
            $list[$k]['orderedAt'] = $r['orderedAt']->format('Y-m-d H:i:s');
        }

        if (isset($_GET['type']) && $_GET['type'] === "pdf") {
            return OrderReportGenerator::generatePdf($list, 'full');
        } elseif (isset($_GET['type']) && $_GET['type'] == 'excel') {
            return OrderReportGenerator::generateExcel($list, 'full');
        }



        return $this->render(
            'orders/orders.twig',
            [
                'tariffs_entries' => $tariffs_list,
                'error' => $err,
                'message' => $msg,
                'orders' => $list,
                'orderedAt_from' => $filter['orderedAt']['from'] ?? '',
                'orderedAt_to' => $filter['orderedAt']['to'] ?? '',
                'tariff_id' => $filter['tariff_id'] ?? '',
                'name' => $filter['name'] ?? '',
                'uname' => $filter['uname'] ?? '',
                'callback' => '/orders',
                'type' => 'full',
                'title' => 'Таблица заказов',
            ]
        );
    }

    #[Route('/history/orders', name: 'app_history_orders')]
    #[IsGranted('ROLE_DRIVER')]
    public function history_orders(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $orderRepository = $em->getRepository(Order::class);
        $tariffRepository = $em->getRepository(Tariff::class);

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $driver = $user->getDriver();
        [$filter, $err] = OrderValidator::validateFilter($_GET);
        $list = $orderRepository->getFilteredList($filter, null, $driver->getId());

        $msg = '';

        $tariffs_list = $tariffRepository->findAll();

        foreach ($list as $k => $r) {
            $list[$k]['orderedAt'] = $r['orderedAt']->format('Y-m-d H:i:s');
        }

        if (isset($_GET['type']) && $_GET['type'] === "pdf") {
            return $this->generatePdf($list, 'full');
        } elseif (isset($_GET['type']) && $_GET['type'] == 'excel') {
            return $this->generateExcel($list, 'full');
        }

        return $this->render(
            'orders/orders.twig',
            [
                'tariffs_entries' => $tariffs_list,
                'error' => $err,
                'message' => $msg,
                'orders' => $list,
                'orderedAt_from' => $filter['orderedAt']['from'] ?? '',
                'orderedAt_to' => $filter['orderedAt']['to'] ?? '',
                'name' => $filter['name'] ?? '',
                'tariff_id' => $filter['tariff_id'] ?? '',
                'type' => 'history',
                'callback' => '/orders/orderHistory',
                'title' => 'История заказов',
            ]
        );
    }

    #[Route('/history/rides', name: 'app_history_rides')]
    #[IsGranted('ROLE_USER')]
    public function history_rides(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $orderRepository = $em->getRepository(Order::class);
        $tariffRepository = $em->getRepository(Tariff::class);
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }


        [$filter, $err] = OrderValidator::validateFilter($_GET);
        $list = $orderRepository->getFilteredList($filter, $user->getId());
        $tariffs_list = $tariffRepository->findAll();
        $msg = '';

        foreach ($list as $k => $r) {
            $list[$k]['orderedAt'] = $r['orderedAt']->format('Y-m-d H:i:s');
        }

        if (isset($_GET['type']) && $_GET['type'] === "pdf") {
            return $this->generatePdf($list, 'full');
        } elseif (isset($_GET['type']) && $_GET['type'] == 'excel') {
            return $this->generateExcel($list, 'full');
        }

        return $this->render(
            'orders/orders.twig',
            [
                'tariffs_entries' => $tariffs_list,
                'error' => $err,
                'message' => $msg,

                'orders' => $list,
                'orderedAt_from' => $filter['orderedAt']['from'] ?? '',
                'orderedAt_to' => $filter['orderedAt']['to'] ?? '',
                'tariff_id' => $filter['tariff_id'] ?? '',
                'name' => $filter['name'] ?? '',
                'type' => 'rides',
                'callback' => '/history/rides',
                'title' => 'История поездок',
            ]
        );
    }

    #[Route('/order/new', name: 'app_order_new')]
    #[IsGranted('ROLE_USER')]
    public function order_taxi(
        Request $request,
        EntityManagerInterface $em,
    ): Response 
    {
        $orderRepository = $em->getRepository(Order::class);
        $tariffRepository = $em->getRepository(Tariff::class);
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        
        $distance = trim($request->request->get('distance', ''));


        if ($request->isMethod('GET')) {
            $filter = [];
            $ratingFrom = $request->query->get('rating_from');
            $tariffId = $request->query->get('tariff_id');
    
            if ($ratingFrom !== null && $ratingFrom >= 0 && $ratingFrom <= 5) {
                $filter['rating']['from'] = $ratingFrom;
            }
    
            if ($tariffId !== null && $tariffId > 0) {
                $filter['tariff_id'] = $tariffId;
            }

            $list = $orderRepository->getAvailableRides($filter, $distance);
            $avaliable_tariffs = $tariffRepository->findAll();

            return $this->render(
                'orders/orderTaxi.twig',
                [
                    'avaliable_orders' => $list,
                    'avaliable_tariffs' => $avaliable_tariffs,
                    'rating_from' => $filter['rating']['from'] ?? '',
                    'tariff_id' => $filter['tariff_id'] ?? '',
                    'type' => 'full',
                ]
            );
        } else {
            $price = trim($request->request->get('price', ''));
            $driverId = trim($request->request->get('driver_id', ''));
            $tariffId = trim($request->request->get('tariff_id', ''));
            $begin = trim($request->request->get('startPoint', ''));
            $destination = trim($request->request->get('endPoint', ''));

            $success = $orderRepository->addOrder(
                $begin,
                $destination,
                $distance,
                $price,
                $driverId,
                $user->getId(),
                $tariffId
            );
            return new Response(
                $this->json(['message' => $success ? 'New record added!' : 'An error occurred']), 
                $success ? 200 : 400
            );
        }
    }

    
}
