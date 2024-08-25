<?php
function get_item_name($conn, $item_code) {
    $item_name_query = "SELECT item_name FROM productos WHERE item_code = ? LIMIT 1";
    $stmt_name = $conn->prepare($item_name_query);
    $stmt_name->bind_param("s", $item_code);
    $stmt_name->execute();
    $stmt_name->bind_result($item_name);
    $stmt_name->fetch();
    $stmt_name->close();
    return $item_name;
}

function get_data($conn, $item_code) {
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
    $stmt->bind_param("s", $item_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

function get_total($conn, $item_code) {
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
    $stmt_total->bind_param("s", $item_code);
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    $stmt_total->close();
    return $result_total;
}

function get_total_production($conn, $item_code, $year) {
    $total_sql = "
        SELECT
            SUM(CASE WHEN f.year = ? THEN f.value ELSE 0 END) AS total
        FROM faowiki f
        WHERE f.item_code = ?
            AND f.element_code = '5510'
            AND f.area_code = 5000;
    ";
    $stmt_total = $conn->prepare($total_sql);
    $stmt_total->bind_param("ss", $year, $item_code);
    $stmt_total->execute();
    $stmt_total->bind_result($total_production);
    $stmt_total->fetch();
    $stmt_total->close();
    return $total_production;
}


function get_highest_producer($conn, $item_code, $year) {
    $highest_producer_query = "
        SELECT p.nombre
        FROM faowiki f
        JOIN paises p ON f.area_code = p.area_code
        WHERE f.item_code = ?
            AND f.year = ?
            AND f.area_code != 5000
        ORDER BY f.value DESC
        LIMIT 1
    ";
    $stmt_highest_producer = $conn->prepare($highest_producer_query);
    $stmt_highest_producer->bind_param("ss", $item_code, $year);
    $stmt_highest_producer->execute();
    $stmt_highest_producer->bind_result($highest_producer);
    $stmt_highest_producer->fetch();
    $stmt_highest_producer->close();
    return $highest_producer;
}

function get_highest_producer_percentage($conn, $item_code, $year) {
    $total_production = get_total_production($conn, $item_code, $year);
    $highest_producer_production = get_highest_producer_production($conn, $item_code, $year);
    $percentage = ($highest_producer_production / $total_production) * 100;
    return round($percentage, 2);
}



function get_highest_producer_production($conn, $item_code) {
    $sql = "
        SELECT
            MAX(CASE WHEN f.year = 2022 THEN f.value END) AS '2022'
        FROM faowiki f
        JOIN paises p ON f.area_code = p.area_code
        WHERE f.item_code = ?
            AND f.element_code = '5510'
            AND (f.area_code < 1000 OR f.area_code = 5000)
            AND f.area_code != 351
            AND f.area_code != 5000
        ORDER BY f.value DESC
        LIMIT 1;
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $item_code);
    $stmt->execute();
    $stmt->bind_result($highest_producer_value);
    $stmt->fetch();
    $stmt->close();
    return $highest_producer_value;
}

