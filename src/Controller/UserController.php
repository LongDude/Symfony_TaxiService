<?php

namespace App\Controller;

use App\Entity\User;
use App\Validators\UserValidator;
use Doctrine\ORM\EntityManagerInterface;
use Fawno\FPDF\FawnoFPDF;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UserController extends AbstractController
{
    #[Route('/users', name: 'app_users')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
    ): Response
    {
        $userRepository = $em->getRepository(User::class);

        [$filter, $err] = UserValidator::validateFilter($_GET);
        $list = $userRepository->getFilteredList($filter);
        $msg = '';

        if ($err !== '') {
            $_SESSION['error'] = $err;
        }

        if (isset($_GET['type']) && $_GET['type'] === "pdf") {
            return $this->generatePdf($list);
        } elseif (isset($_GET['type']) && $_GET['type'] == 'excel') {
            return $this->generateExcel($list);
        }

        return $this->render(
            'user/index.html.twig',
            [
                'message' => $msg,
                'callback' => '/users',
                'users' => $list,
                'name' => $filter["name"] ?? "",
                'phone' => $filter["phone"] ?? "",
                'email' => $filter["email"] ?? "",
            ]
        );
    }

    #[Route('/users/upload', name: 'app_users_upload')]
    #[IsGranted('ROLE_ADMIN')]
    public function users_upload(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        ): Response {
        $userRepository = $em->getRepository(User::class);
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
                if (count($newline) < 4){
                    return new Response("Некорректный CSV: ожидалось 4 столбца", 400);
                    // return new Response($this->json($data), 400);
                }
                $data[] = $newline;
            }

            $count = $userRepository->importCsv($data, $hasher);
            return new Response("Успешно загружено $count строк", 200);
        } catch (\Exception $e){
            return new Response($e->getMessage(), 500);
        }
    }

    private function generatePdf(array $data)
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
        $pdf->Cell(65, 10, toWin1251('Почта'), 1);
        $pdf->Cell(50, 10, toWin1251('Роль'), 1);

        $pdf->Ln();
        $pdf->SetFont($fontname, '', 12);
        foreach ($data as $row) {
            $pdf->Cell(60, 10, toWin1251($row['name']), 1);
            $pdf->Cell(45, 10, toWin1251($row['phone']), 1);
            $pdf->Cell(65, 10, toWin1251($row['email']), 1);
            $pdf->Cell(50, 10, toWin1251(implode(';', $row['roles'])), 1);
            $pdf->Ln();
        }
        $pdfContent = $pdf->Output('S', 'report.pdf');

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="users_report.pdf"');
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');
        return $response;
    }

    private function generateExcel(array $data)
    {
        $spreadsheet = new Spreadsheet();
        $cells = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        $sheet = $spreadsheet->getActiveSheet();

        $i = 0;
        $sheet->setCellValue($cells[$i++] . '1', 'Имя');
        $sheet->setCellValue($cells[$i++] . '1', 'Номер телефона');
        $sheet->setCellValue($cells[$i++] . '1', 'Почта');
        $sheet->setCellValue($cells[$i++] . '1', 'Роль');

        $rowIndex = 2;
        foreach ($data as $row) {
            $i = 0;
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['name']);
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['phone']);
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['email']);
            $sheet->setCellValue($cells[$i++] . $rowIndex, implode(';',$row['roles']));
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
