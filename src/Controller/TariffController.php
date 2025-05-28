<?php

namespace App\Controller;

use App\Entity\Tariff;
use App\Form\TariffForm;
use App\Validators\TariffValidator;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\TariffReportGenerator;
use Fawno\FPDF\FawnoFPDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TariffController extends AbstractController
{
    #[Route('/tariffs/list', name: 'app_tariffs_list')]
    #[IsGranted('ROLE_USER')]
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
            $tariff_data[] = $new_line;
        };
        return $this->render(
            'tariff/tariffs_list.twig',
            [
                'tariffs' => $tariff_data,
            ]
        );
    }
    

    #[Route('/tariffs/table', name: 'app_tariffs_table')]
    #[IsGranted('ROLE_ADMIN')]
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
            return TariffReportGenerator::generatePdf($list);
        } elseif ($type === 'excel') {
            return TariffReportGenerator::generateExcel($list);
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

    #[Route('/tariffs/table/upload', name: 'app_tariffs_table_upload')]
    #[IsGranted('ROLE_ADMIN')]
    public function tariffs_table_upload(
        Request $request,
        EntityManagerInterface $em,
        ): Response {
        $tariffRepository = $em->getRepository(Tariff::class);
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
                $data[] = str_getcsv(trim($line), ';');
            }

            $count = $tariffRepository->importCsv($data);
            return new Response("Успешно загружено $count строк", 200);
        } catch (\Exception $e){
            return new Response($e->getMessage(), 500);
        }
    }


    #[Route('/tariffs/add', name:'app_tariffs_add', methods: ['POST', 'GET'])]
    #[IsGranted('ROLE_ADMIN')]
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

    
}
