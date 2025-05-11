<?php
namespace App\Core;

interface ModelValidator{
    public static function validateData(array $data): string;
    public static function validateFilter(array $data): array;
}