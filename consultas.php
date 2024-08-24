<?php
// queries.php

function get_country_data($conn, $item_code) {
    $sql = "
    SELECT 
        CASE 
            WHEN p.nombre = 'República Democrática Popular de Etiopía' THEN 'Etiopía'
            WHEN p.nombre = 'República Democrática de Sudán' THEN 'Sudán'
            ELSE p.nombre
        END AS Pais,
        MAX(CASE WHEN f.year = 1961 THEN f.value END) AS '1961',
        MAX(CASE WHEN f.year = 1970 THEN f.value END) AS '1970',
        MAX(CASE WHEN f.year = 1980 THEN f.value END) AS '1980',
        MAX(CASE WHEN f.year = 1990 THEN f.value END) AS '1990',
        MAX(CASE WHEN f.year = 2000 THEN f.value END) AS '2000',
        MAX(CASE WHEN f.year = 2010 THEN f.value END) AS '2010',
        MAX(CASE WHEN f.year = 2020 THEN f.value END) AS '2020',
        MAX(CASE WHEN f.year = 2022 THEN f.value END) AS '2022',
        ROW_NUMBER() OVER (ORDER BY MAX(CASE WHEN f.year = 2022 THEN f.value END) DESC) AS ranking_2022
    FROM faowiki f
    JOIN paises p ON f.area_code = p.area_code
    WHERE f.item_code = ? 
        AND f.element_code = '5510'
        AND (f.area_code < 1000 OR f.area_code = 5000)
        AND f.area_code != 351
        AND f.area_code != 5000
    GROUP BY 
        CASE 
            WHEN p.nombre = 'República Democrática Popular de Etiopía' THEN 'Etiopía'
            WHEN p.nombre = 'República Democrática de Sudán' THEN 'Sudán'
            ELSE p.nombre
        END
    ORDER BY ranking_2022;
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Error en la preparación de la consulta: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
    }

    $stmt->bind_param("s", $item_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    return $result;
}

function get_total_data($conn, $item_code) {
    $total_sql = "
    SELECT 
        'Total' AS Pais,
        SUM(CASE WHEN f.year = 1961 THEN f.value ELSE 0 END) AS '1961',
        SUM(CASE WHEN f.year = 1970 THEN f.value ELSE 0 END) AS '1970',
        SUM(CASE WHEN f.year = 1980 THEN f.value ELSE 0 END) AS '1980',
        SUM(CASE WHEN f.year = 1990 THEN f.value ELSE 0 END) AS '1990',
        SUM(CASE WHEN f.year = 2000 THEN f.value ELSE 0 END) AS '2000',
        SUM(CASE WHEN f.year = 2010 THEN f.value ELSE 0 END) AS '2010',
        SUM(CASE WHEN f.year = 2020 THEN f.value ELSE 0 END) AS '2020',
        SUM(CASE WHEN f.year = 2022 THEN f.value ELSE 0 END) AS '2022'
    FROM faowiki f
    WHERE f.item_code = ? 
        AND f.element_code = '5510'
        AND f.area_code = 5000;
    ";

    $stmt_total = $conn->prepare($total_sql);
    if ($stmt_total === false) {
        die('Error en la preparación de la consulta de total: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
    }

    $stmt_total->bind_param("s", $item_code);
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    $stmt_total->close();

    return $result_total;
}

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
?>
