<?php
namespace App\Service;

use Fawno\FPDF\FawnoFPDF;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TariffReportGenerator{
    public static function generatePdf(array $data): Response
    {
        function toWin1251(?string $text): ?string {
            if ($text === null){
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
        $pdf->Cell(20, 10, toWin1251('Название тарифа'), 1);
        $pdf->Cell(20, 10, toWin1251('Начальная стоимость'), 1);
        $pdf->Cell(20, 10, toWin1251('Расстояние в тарифе'), 1);
        $pdf->Cell(20, 10, toWin1251('Стоимость за км'), 1);

        $pdf->Ln();
        $pdf->SetFont($fontname, '', 12);
        foreach ($data as $row) {
            $pdf->Cell(20, 10, toWin1251($row['name']), 1);
            $pdf->Cell(20, 10, toWin1251($row['base_price']), 1);
            $pdf->Cell(20, 10, toWin1251($row['base_dist']), 1);
            $pdf->Cell(20, 10, toWin1251($row['dist_cost']), 1);
            $pdf->Ln();
        }
        $pdfContent = $pdf->Output('S', 'report.pdf');

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="tariffs_report.pdf"');
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');
        return $response;
    }

    public static function generateExcel(array $data)
    {
        $spreadsheet = new Spreadsheet();
        $cells = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        $sheet = $spreadsheet->getActiveSheet();

        $i = 0;
        $sheet->setCellValue($cells[$i++] . '1', 'Название тарифа');
        $sheet->setCellValue($cells[$i++] . '1', 'Начальная стоимость');
        $sheet->setCellValue($cells[$i++] . '1', 'Расстояние в тарифе');
        $sheet->setCellValue($cells[$i++] . '1', 'Стоимость за км');

        $rowIndex = 2;
        foreach ($data as $row) {
            $i = 0;
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['name']);
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['base_price']);
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['base_dist']);
            $sheet->setCellValue($cells[$i++] . $rowIndex, $row['dist_cost']);
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