<?php
namespace src\Files;

use src\Validators\ModelValidator;

class BaseUploader
{
    public static function validateCsv(string $file, array $fields, ModelValidator $modelValidator): string
    {
        $err = '';
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        finfo_close($finfo);

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($extension) !== 'csv') {
            $err .= "INVALID FILE EXTENSION\n";
        }

        if ($file['size'] > (1 << 21)) {
            $err .= "FILE TOO BIG\n";
        }

        if (($filehandle = fopen($file['tmp_name'], 'r')) === false) {
            $err .= "FILE ERROR\n";
        }

        $count = 0;
        while (($row = fgetcsv($filehandle, 1000, '.')) !== false) {
            $count++;

            if (count($row) !== count($fields)) {
                $err .= "Error in row $count; Ожидалось " . count($fields) . "столбца (" . implode(",", $fields) . ")\n";
                break;
            }

            $data = array();
            foreach (array_values(array_map('trim', $row)) as $i => $value) {
                $data[$fields[$i]] = $value;
            }

            $validResult = $modelValidator::validateData($data);
            if ($validResult !== "") {
                $err .= "LINE $count:" . $validResult . "\n";
            }
        }
        fclose($filehandle);
        return $err;
    }

    public static function saveCsv($file): string
    {
        $fileName = "lastData.csv";
        $fileTmpPath = $file['tmp_name'];
        $uploadDir = __DIR__ . '/Uploads/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . basename($fileName);
        if (!move_uploaded_file($fileTmpPath, $destination)) {
            return "Error uploading file.\n";
        }
        return "";
    }
}
?>