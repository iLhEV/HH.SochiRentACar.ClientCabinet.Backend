<?php

namespace App\Library;

class FormatData
{
    public static $phone_pattern = '#^[0-9\-\(\)\+\s]{0,40}$#';
    public static $names_pattern = '#^[\x{0400}-\x{04FF}A-z\s]{0,100}$#iu';

    public static function formatPhoneForDb($phone)
    {
        return str_replace(['(', ')', ' ', '-'], '', $phone);
    }
}