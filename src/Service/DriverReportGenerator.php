<?php
namespace App\Service;

use Fawno\FPDF\FawnoFPDF;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DriverReportGenerator{
    public static function generatePdf(array $data): Response
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

    public static function generateExcel(array $data)
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