<?php

if (!function_exists('generateOtp')) {
    function generateOtp(): string
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
