<?php

namespace App\Controller;

use App\Entity\Driver;
use App\Form\DriverForm;
use App\Validators\DriverValidator;
use Doctrine\ORM\EntityManagerInterface;
use Fawno\FPDF\FawnoFPDF;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\User;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DriverController extends AbstractController
{
    #[Route('/drivers', name: 'app_driver')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
    ): Response
    {
        
        $driverRepository = $em->getRepository(Driver::class);

        [$filter, $err] = DriverValidator::validateFilter($_GET);
        $list = $driverRepository->getFilteredList($filter);
        $msg = '';
        if ($err !== '') {
            $_SESSION['error'] = $err;
        }

        if (isset($_GET['type']) && $_GET['type'] == 'pdf') {
            return $this->generatePdf($list);
        } elseif (isset($_GET['type']) && $_GET['type'] == 'excel') {
            return $this->generateExcel($list);
        }

        return $this->render(
            'driver/index.html.twig',
            [
                'drivers' => $list,
                'message' => $msg,
                'callback' => '/drivers',
                'name' => $filter["name"] ?? "",
                'phone' => $filter["phone"] ?? "",
                'email' => $filter["email"] ?? "",
                'car_license' => $filter["car_license"] ?? "",
                'tariff_id' => $filter["tariff_id"] ?? "",
            ]
        );
    }

    #[Route('/drivers/upload', name: 'app_driver_upload')]
    #[IsGranted('ROLE_ADMIN')]
    public function drivers_upload(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        ): Response {
        $driverRepository = $em->getRepository(Driver::class);
        $file = $request->files->get('csv-file');

        if (!$file || !$file->isValid()){
            return new Response('Ошибка чтения файла', 400);
        }
        if ($file->getClientMimeType() !== 'text/csv'){
            return new Response('Только CSV файлы разрешены', 400);
        }

        try {
            $content = $file->getContent();
            $lines = explode("\n", $content);
            $data = [];
            foreach ($lines as $line){
                if (empty(trim($line))) continue;
                $newline = str_getcsv(trim($line), ';');
                if (count($newline) == 0) continue;
                if (count($newline) < 8){
                    return new Response("Некорректный CSV: ожидалось 8 столбцов", 400);
                    return new Response($this->json($newline), 400);
                }
                $data[] = $newline;
            }

            $count = $driverRepository->importCsv($data, $hasher);
            return new Response("Успешно загружено $count строк", 200);
        } catch (\Exception $e){
            return new Response($e->getMessage(), 500);
        }
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
    private function generatePdf(array $data): Response
    {

        function toWin1251(?string $text): ?string {
            if ($text === null){
                return null;
            }
            return iconv('UTF-8', 'windows-1251//IGNORE', $text);
        }

        define('FPDF_FONTPATH','../../public/fonts');
        $pdf = new FawnoFPDF();
        $pdf->AddPage('L');
        $fontname = 'Iosevka';
        

        $pdf->AddFont($fontname, '', 'IosevkaNerdFont_Regular.php', '/var/www/html/public/fonts/unifont');
        $pdf->AddFont($fontname, 'B', 'IosevkaNerdFont-Bold.php', '/var/www/html/public/fonts/unifont');

        // $pdf->SetFont('DejaVuSerif.ttf', 'B', 12);
        $pdf->SetFont($fontname, 'B', 12);
        $pdf->Cell(60, 10, toWin1251('Имя'), 1);
        $pdf->Cell(45, 10, toWin1251('Номер телефона'), 1);
        $pdf->Cell(60, 10, toWin1251('Почта'), 1);
        $pdf->Cell(15, 10, toWin1251('Стаж'), 1);
        $pdf->Cell(30, 10, toWin1251("Лицензионный номер"), 1);
        $pdf->Cell(60, 10, toWin1251('Марка машины'), 1);
        $pdf->Cell(60, 10, toWin1251('Название тариффа'), 1);

        $pdf->Ln();

        $pdf->SetFont($fontname, 'B', 12);

        foreach ($data as $row) {
            $pdf->Cell(60, 10, toWin1251($row['name']), 1);
            $pdf->Cell(45, 10, toWin1251($row['phone']), 1);
            $pdf->Cell(60, 10, toWin1251($row['email']), 1);
            $pdf->Cell(15, 10, toWin1251($row['intership']), 1);
            $pdf->Cell(30, 10, toWin1251($row['car_license']), 1);
            $pdf->Cell(60, 10, toWin1251($row['car_brand']), 1);
            $pdf->Cell(60, 10, toWin1251($row['tariff_name']), 1);
            $pdf->Ln();
        }
        $pdfContent = $pdf->Output('S', 'report.pdf');

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="driver_report.pdf"');
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');
        return $response;
    }

    private function generateExcel(array $data)
    {
        $spreadsheet = new Spreadsheet();
        $cells = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        $sheet = $spreadsheet->getActiveSheet();

        $i = 0;
        $sheet->setCellValue($cells[$i++] . '1', 'Имя',);
        $sheet->setCellValue($cells[$i++] . '1', 'Номер телефона');
        $sheet->setCellValue($cells[$i++] . '1', 'Почта',);
        $sheet->setCellValue($cells[$i++] . '1', 'Стаж',);
        $sheet->setCellValue($cells[$i++] . '1', 'Лицензионный номер');
        $sheet->setCellValue($cells[$i++] . '1', 'Марка машины');
        $sheet->setCellValue($cells[$i++] . '1', 'Название тариффа');

        $rowIndex = 2;
        foreach ($data as $row) {
            $i = 0;
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['name']);
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['phone']);
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['email']);
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['intership']);
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['car_license']);
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['car_brand']);
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['tariff_name']);
            $rowIndex++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="report.xlsx"');

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        $response = new BinaryFileResponse($tempFile);
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');
        $response->deleteFileAfterSend(true);
        return $response;  
    }
}
