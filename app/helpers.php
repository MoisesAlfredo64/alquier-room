<?php

if (!function_exists('formatNumber')) {
    /**
     * Formatea números con separador de miles (punto) y decimales (coma)
     * Ejemplo: 2000000 -> 2.000.000
     * 
     * @param float|int $number
     * @param int $decimals
     * @return string
     */
    function formatNumber($number, $decimals = 0)
    {
        if (is_null($number)) {
            return '0';
        }
        return number_format($number, $decimals, ',', '.');
    }
}
