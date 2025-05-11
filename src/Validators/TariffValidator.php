<?php

namespace App\Validators;
require_once 'BaseValidators.php';

class TariffValidator implements ModelValidator
{
    public static function validateData(array $data): string
    {
        $name = trim($data['name'] ?? '');
        $base_price = trim($data['base_price'] ?? '');
        $base_dist = trim($data['base_dist'] ?? '');
        $dist_cost = trim($data['dist_cost'] ?? '');
        $err = "";

        if (strlen($name) == 0 || strlen($name) > 20) {
            $err .= "INVALID name DATA;";
        }

        if (!is_numeric($base_price) or $base_price < 0) {
            $err .= "INVALID base_price DATA;";
        }

        if (!is_numeric($base_dist) or $base_dist < 0) {
            $err .= "INVALID base_dist DATA;";
        }

        if (!is_numeric($dist_cost) or $dist_cost < 0) {
            $err .= "INVALID dist_cost DATA;";
        }

        return $err;
    }

    public static function validateFilter(array $data): array
    {
        $err = "";
        if (isset($data['name'])) {
            if (strlen($data['name']) == 0) {
                unset($data['name']);
                $err .= "INVALID name FILTER;";
            } else {
                $data['name'] = trim($data['name']);
            }
        }

        if (isset($data['base_price_from'])) {
            if ($data['base_price_from'] < 0 or $data['base_price_from'] > 100000) {
                $err .= "INVALID base_price_from FILTER;";

            } else {
                $data['base_price']['from'] = trim($data['base_price_from']);
            }
            unset($data['base_price_from']);
        }

        if (isset($data['base_price_to'])) {
            if ($data['base_price_to'] < 0 or $data['base_price_to'] > 100000) {
                $err .= "INVALID base_price_to FILTER;";

            } else {
                $data['base_price']['to'] = trim($data['base_price_to']);
            }
            unset($data['base_price_to']);
        }

        return array($data, $err);
    }
}
?>