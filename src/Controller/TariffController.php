<?php

namespace App\Controller;

use App\Entity\Tariff;
use App\Form\TariffForm;
use App\Validators\TariffValidator;
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

final class TariffController extends AbstractController
{
    #[Route('/tariffs/list', name: 'app_tariffs_list')]
    public function tariffs_list(
        EntityManagerInterface $em,
    ): Response {
        $tariffRepository = $em->getRepository(Tariff::class);
        $tariff_data = [];
        foreach ($tariffRepository->findAll() as $tariff){
            $new_line = [];
            $new_line['name'] = $tariff->getName();
            $new_line['base_price'] = $tariff->getBasePrice();
            $new_line['base_dist'] = $tariff->getBaseDist();
            $new_line['dist_cost'] = $tariff->getDistCost();
        };
        return $this->render(
            'tariff/tariffs_list.twig',
            [
                'tariffs' => $tariff_data,
            ]
        );
    }
    

    #[Route('/tariffs/table', name: 'app_tariffs_table')]
    public function tariffs_table(
        Request $request,
        EntityManagerInterface $em,
    ): Response
    {
        $tariffRepository = $em->getRepository(Tariff::class);

        $type = $request->query->get('type');
        [$filter, $err] = TariffValidator::validateFilter($request->query->all());
        $list = $tariffRepository->getFilteredList($filter);

        $msg = '';

        if ($type === 'pdf') {
            return $this->generatePdf($list);
        } elseif ($type === 'excel') {
            return $this->generateExcel($list);
        }

        return $this->render(
            'tariff/tariffs.twig',
            [
                'tariffs' => $list,
                'message' => $msg,
                'error' => $err,
                'name' => $filter["name"] ?? "",
                'callback' => '/tariffs/table',
                'base_price_from' => $filter["base_price"]["from"] ?? "",
                'base_price_to' => $filter["base_price"]["to"] ?? "",
            ]
        );
    }

    #[Route('/tariffs/add', name:'app_tariffs_add', methods: ['POST', 'GET'])]
    public function tariffs_new(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $tariff = new Tariff();
        $form = $this->createForm(
            TariffForm::class,
            $tariff
        );

        $form ->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $em->persist($tariff);
            $em->flush();

            $this->addFlash('success', 'Добавлен новый тариф');
            return $this->redirectToRoute('app_tariffs_table');
        }
        return $this->render('tariff/addTariff.twig', [
            'tariffForm' => $form,
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

        $pdf = new FawnoFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(20, 10, toWin1251('Название тарифа'), 1);
        $pdf->Cell(20, 10, toWin1251('Начальная стоимость'), 1);
        $pdf->Cell(20, 10, toWin1251('Расстояние в тарифе'), 1);
        $pdf->Cell(20, 10, toWin1251('Стоимость за км'), 1);

        $pdf->Ln();

        $pdf->SetFont('Arial', '', 12);
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

    private function generateExcel(array $data)
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
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'tariffs_report.xlsx');
        return $response;    
    }
}
