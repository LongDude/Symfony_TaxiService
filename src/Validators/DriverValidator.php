<?php

namespace App\Validators;
use App\Core\ModelValidator;
use App\Core\BaseValidators;

class DriverValidator implements ModelValidator
{
    public static function validateData(array $data): string
    {
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = trim($data['email'] ?? '');
        $intership = trim($data['intership'] ?? '');
        $car_license = trim($data['car_license'] ?? '');
        $car_brand = trim($data['car_brand'] ?? '');
        $tariff_id = trim($data['tariff_id'] ?? '');
        $err = "";

        $err .= BaseValidators::nameValidator($name);
        $err .= BaseValidators::phoneValidator($phone);
        $err .= BaseValidators::emailValidator($email);

        // Валидация стажа
        if (strlen($intership) == 0 || $intership <= 0 || $intership >= 100) {
            $err .= "INVALID intership DATA;";
        }

        // Валидация регистрационного номера машины
        if (!preg_match("/^[а-яA-Z0-9]{4,8}[ -][а-яЫA-Z0-9]{2,4}$/ui", $car_license)) {
            $err .= "INVALID car_license DATA;";
        }

        if (strlen($car_brand) == 0 || strlen($car_brand) > 50) {
            $err .= "INVALID CAR car_brand DATA;";
        }

        if (!is_numeric($tariff_id) || $tariff_id < 0) {
            $err .= "INVALID tariff_id DATA;";
        }
        return $err;
    }

    public static function validateFilter(array $data): array
    {
        // Удаляет некорректные фильтры
        $err = "";

        // Фильтрация стажа
        // if (isset($data['intership_from'])) {
        //     if ($data['intership_from'] < 0 or $data['intership_from'] > 100) {
        //         $err .= "INVALID intership_from FILTER;";
        //     } else {
        //         $data['intership']['from'] = trim($data['intership_from']);
        //     }
        //     unset($data['intership_from']);
        // }

        // if (isset($data['intership_to'])) {
        //     if ($data['intership_to'] < 0 or $data['intership_to'] > 100) {
        //         $err .= "INVALID intership_to FILTER;";
        //     } else {
        //         $data['intership']['to'] = trim($data['intership_to']);
        //     }
        //     unset($data['intership_to']);
        // }

        if (isset($data['name'])) {
            if (strlen($data['name']) == 0) {
                $err .= "INVALID name FILTER;";
                unset($data['name']);
            } else {
                $data['name'] = trim($data['name']);
            }
        }

        if (isset($data['phone'])) {
            if (strlen($data['phone']) == 0){
                unset($data['phone']);
                $err .= "INVALID phone FILTER;";
            } else {
                $data['phone'] = trim($data['phone']);
            }
        }

        if (isset($data['email'])){
            if (strlen($data['email']) == 0){
                unset($data['email']);
                $err .= "INVALID email FILTER;";
            } else {
                $data['email'] = trim($data['email']);
            }
        }

        // if (isset($data['car_brand'])){
        //     if (strlen($data['car_brand']) == 0){
        //         unset($data['car_brand']);
        //         $err .= "INVALID car_brand FILTER;";
        //     } else {
        //         $data['car_brand'] = trim($data['car_brand']);
        //     }
        // }

        // Фильтрация лицензионой платы
        if (isset($data['car_license'])){
            if (!preg_match("/^[а-яA-Z0-9]{4,8}[ -][а-яЫA-Z0-9]{2,4}$/ui", $data['car_license'])){
                unset($data['car_license']);
                $err .= "INVALID car_license FILTER;";
            } else {
                $data['car_license'] = trim($data['car_license']);
            }
        }

        // Фильтрация тарифа
        if (isset($data['tariff_id'])){
            if (!is_numeric($data['tariff_id']) || $data['tariff_id'] < 0){
                unset($data['tariff_id']);
                $err .= "INVALID tariff_id FILTER;";
            } else {
                $data['tariff_id'] = trim($data['tariff_id']);
            }
        }

        return array($data, $err);
    }
}
?>