<?php
namespace App\Validators;
use DateTime;


interface ModelValidator{
    public static function validateData(array $data): string;
    public static function validateFilter(array $data): array;
}

class BaseValidators
{
    public static function emailValidator($email): string
    {
        // Валидация почты
        if (!preg_match("/^[a-zA-Z]\S*@[a-zA-Z]+\.[a-zA-Z]+$/", $email)) {
            return "INVALID email DATA;";
        }
        return "";
    }

    public static function phoneValidator($phone): string
    {
        // Валидация номера телефона
        if (!preg_match("/\+7 \([0-9]{3}\) [0-9]{3}-[0-9]{2}-[0-9]{2}/", $phone)) {
            return "INVALID phone DATA;";
        }
        return "";
    }

    public static function nameValidator($name): string
    {
        // Валидация имени
        if (!preg_match("/^[a-zA-Zа-яА-Я][a-zA-Zа-яА-Я ]{3,49}\$/ui", $name)) {
            return "INVALID name DATA;";
        }
        return "";
    }

    public static function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
    public static function formatDate($date)
    {
        $d = new DateTime()->setTimestamp(strtotime($date));
        if (!$d || $d->getTimestamp() == 0){
            return "";
        } else {
            return $d->format('Y-m-d H:i:s');
        }
    }
}

?>