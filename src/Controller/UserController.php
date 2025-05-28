<?php

namespace App\Controller;

use App\Entity\User;
use App\Validators\UserValidator;
use App\Service\UserReportGenerator;
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
            return UserReportGenerator::generatePdf($list);
        } elseif (isset($_GET['type']) && $_GET['type'] == 'excel') {
            return UserReportGenerator::generateExcel($list);
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
}
