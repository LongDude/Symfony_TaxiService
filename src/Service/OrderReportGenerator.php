<?php
namespace App\Service;


use Fawno\FPDF\FawnoFPDF;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


class OrderReportGenerator{
    public function generatePdf(array $data, string $reportType): Response
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
        $pdf->Cell(20, 10, toWin1251('Начальная точка'), 1);
        $pdf->Cell(20, 10, toWin1251('Конечная точка'), 1);
        $pdf->Cell(20, 10, toWin1251('Расстояние'), 1);
        $pdf->Cell(20, 10, toWin1251('Время заказа'), 1);

        if ($reportType != 'rides') {
            $pdf->Cell(20, 10, toWin1251('Имя водителя'), 1);
        }
        if ($reportType == 'full') {
            $pdf->Cell(20, 10, toWin1251('Имя клиента'), 1);
        }

        $pdf->Cell(20, 10, toWin1251('Тарифф'), 1);
        $pdf->Cell(20, 10, toWin1251('Стоимость'), 1);

        $pdf->Ln();

        $pdf->SetFont($fontname, '', 12);
        foreach ($data as $row) {
            if ($reportType != 'history') {
                $pdf->Cell(20, 10, $row['phone'], 1);
            }
            $pdf->Cell(20, 10, toWin1251($row['from_loc']), 1);
            $pdf->Cell(20, 10, toWin1251($row['dest_loc']), 1);
            $pdf->Cell(20, 10, toWin1251($row['distance']), 1);
            $pdf->Cell(20, 10, toWin1251($row['orderedAt']), 1);

            if ($reportType != 'rides') {
                $pdf->Cell(20, 10, toWin1251($row['driver_name']), 1);
            }
            if ($reportType == 'full') {
                $pdf->Cell(20, 10, toWin1251($row['user_name']), 1);
            }

            $pdf->Cell(20, 10, toWin1251($row['tariff_name']), 1);
            $pdf->Cell(20, 10, toWin1251($row['price']), 1);
            $pdf->Ln();
        }
        $pdfContent = $pdf->Output('S', 'report.pdf');

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="order_report.pdf"');
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');
        return $response;
    }

    public function generateExcel(array $data, string $reportType)
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
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        $response = new BinaryFileResponse($tempFile);
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');
        $response->deleteFileAfterSend(true);
        return $response;   
    }
}