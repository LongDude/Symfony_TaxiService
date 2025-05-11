<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Fawno\FPDF\FawnoFPDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Validators\OrderValidator;
use App\Entity\Order;
use App\Entity\Tariff;
use App\Entity\User;
final class OrderController extends AbstractController
{
    #[Route('/orders', name: 'app_order')]
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
        if (isset($_GET['type']) && $_GET['type'] === "pdf") {
            $this->generatePdf($list, 'full');
            $msg = "Отчет успешно составлен\n";
        } elseif (isset($_GET['type']) && $_GET['type'] == 'excel') {
            $this->generateExcel($list, 'full');
            $msg = "Отчет успешно составлен\n";
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

        if (isset($_GET['type']) && $_GET['type'] === "pdf") {
            $this->generatePdf($list, 'full');
            $msg = "Отчет успешно составлен\n";
        } elseif (isset($_GET['type']) && $_GET['type'] == 'excel') {
            $this->generateExcel($list, 'full');
            $msg = "Отчет успешно составлен\n";
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

        if (isset($_GET['type']) && $_GET['type'] === "pdf") {
            $this->generatePdf($list, 'full');
            $msg = "Отчет успешно составлен\n";
        } elseif (isset($_GET['type']) && $_GET['type'] == 'excel') {
            $this->generateExcel($list, 'full');
            $msg = "Отчет успешно составлен\n";
        }

        foreach ($list as $k => $r) {
            $list[$k]['orderedAt'] = $r['orderedAt']->format('Y-m-d H:i:s');
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

            $list = $orderRepository->getAvailableRides($filter);
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
            $driverId = trim($request->request->get('driver_id', ''));
            $begin = trim($request->request->get('startPoint', ''));
            $destination = trim($request->request->get('endPoint', ''));
            $distance = trim($request->request->get('distance', ''));

            $success = $orderRepository->addOrder(
                $begin,
                $destination,
                $distance,
                $driverId,
                $user->getId(),
            );
            return new Response(
                $this->json(['message' => $success ? 'New record added!' : 'An error occurred']), 
                $success ? 200 : 400
            );
        }
    }

    private function generatePdf(array $data, string $reportType)
    {
        function toWin1251(?string $text): ?string
        {
            if ($text === null) {
                return null;
            }
            return iconv('UTF-8', 'windows-1251//IGNORE', $text);
        }

        define('FPDF_FONTPATH', '../../public/fonts');
        $pdf = new FawnoFPDF();
        $pdf->AddPage('L');
        $fontname = 'Iosevka';


        $pdf->AddFont($fontname, '', 'IosevkaNerdFont_Regular.php', '/var/www/html/public/fonts/unifont');
        $pdf->AddFont($fontname, 'B', 'IosevkaNerdFont-Bold.php', '/var/www/html/public/fonts/unifont');

        // $pdf->SetFont('DejaVuSerif.ttf', 'B', 12);
        $pdf->SetFont($fontname, 'B', 12);
        $pdf->Cell(20, 10, 'Начальная точка', 1);
        $pdf->Cell(20, 10, 'Конечная точка', 1);
        $pdf->Cell(20, 10, 'Расстояние', 1);
        $pdf->Cell(20, 10, 'Время заказа', 1);

        if ($reportType != 'rides') {
            $pdf->Cell(20, 10, 'Имя водителя', 1);
        }
        if ($reportType == 'full') {
            $pdf->Cell(20, 10, 'Имя клиента', 1);
        }

        $pdf->Cell(20, 10, 'Тарифф', 1);
        $pdf->Cell(20, 10, 'Стоимость', 1);

        $pdf->Ln();

        $pdf->SetFont($fontname, '', 12);
        foreach ($data as $row) {
            if ($reportType != 'history') {
                $pdf->Cell(20, 10, $row['phone'], 1);
            }
            $pdf->Cell(20, 10, $row['from_loc'], 1);
            $pdf->Cell(20, 10, $row['dest_loc'], 1);
            $pdf->Cell(20, 10, $row['distance'], 1);
            $pdf->Cell(20, 10, $row['orderedAt'], 1);

            if ($reportType != 'rides') {
                $pdf->Cell(20, 10, $row['driver_name'], 1);
            }
            if ($reportType == 'full') {
                $pdf->Cell(20, 10, $row['user_name'], 1);
            }

            $pdf->Cell(20, 10, $row['tariff_name'], 1);
            $pdf->Cell(20, 10, $row['price'], 1);
            $pdf->Ln();
        }
        $pdf->Output('I', 'report.pdf');
        exit;
    }

    private function generateExcel(array $data, string $reportType)
    {
        $spreadsheet = new Spreadsheet();
        $cells = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        $sheet = $spreadsheet->getActiveSheet();

        $i = 0;
        if ($reportType != 'history') {
            $sheet->setCellValue($cells[$i++] . '1', 'Номер телефона');
        }
        $sheet->setCellValue($cells[$i++] . '1', 'Начальная точка');
        $sheet->setCellValue($cells[$i++] . '1', 'Конечная точка');
        $sheet->setCellValue($cells[$i++] . '1', 'Расстояние');
        $sheet->setCellValue($cells[$i++] . '1', 'Время заказа');
        if ($reportType != 'rides') {
            $sheet->setCellValue($cells[$i++] . '1', 'Имя водителя');
        }
        if ($reportType == 'full') {
            $sheet->setCellValue($cells[$i++] . '1', 'Имя клиента');
        }
        $sheet->setCellValue($cells[$i++] . '1', 'Тариф');
        $sheet->setCellValue($cells[$i++] . '1', 'Стоимость');

        $rowIndex = 2;
        foreach ($data as $row) {
            $i = 0;
            if ($reportType != 'history') {
                $sheet->setCellValue($cells[$i++] . $rowIndex, $row['phone']);
            }
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['from_loc']);
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['dest_loc']);
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['distance']);
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['orderedAt']);
            if ($reportType != 'rides') {
                $sheet->setCellValue($cells[$i++] . $rowIndex, $row['driver_name']);
            }
            if ($reportType == 'full') {
                $sheet->setCellValue($cells[$i++] . $rowIndex, $row['user_name']);
            }
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['tariff_name']);
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['price']);
            $rowIndex++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="report.xlsx"');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
