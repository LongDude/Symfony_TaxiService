<?php

namespace App\Validators;
require_once 'BaseValidators.php';

class UserValidator implements ModelValidator{
    public static function validateData(array $data): string
    {
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');
        $reppassword = trim($data['repeat_password'] ?? '');
        $err = "";

        $err .= BaseValidators::nameValidator($name);
        $err .= BaseValidators::phoneValidator($phone);
        $err .= BaseValidators::emailValidator($email);
        if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/ui", $password)){
            $err .= "INVALID password DATA;";
        }
        if ($password !== $reppassword){
            $err .= "INVALID reppassword DATA;";
        }
        return $err;
    }
    public static function validateFilter(array $data): array
    {
        $err = "";

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
        return array($data, $err);
    }
}

?>