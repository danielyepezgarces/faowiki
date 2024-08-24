<?php
function format_value($value) {
    if (is_null($value) || $value === '') {
        return ['value' => '-', 'sort' => null];
    }
    $value = str_replace(',', '', $value);  // Remover comas si existen
    $value = floatval($value) / 1000;

    if ($value < 0.1) {
        return ['value' => '<0.1', 'sort' => '0.01'];
    } elseif ($value < 1) {
        return ['value' => number_format($value, 1, '.', ''), 'sort' => null]; // Un decimal
    } elseif ($value >= 1 && $value < 10000) {
        return ['value' => number_format($value, 0, '.', ''), 'sort' => null]; // Sin decimales y sin separador de miles
    } else {
        return ['value' => number_format($value, 0, '.', ' '), 'sort' => null]; // Sin decimales, con espacio como separador de miles
    }
}
