<?php
include 'config.php'; // Archivo con configuración de la base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$item_code = isset($_GET['item_code']) ? $_GET['item_code'] : '221'; // Default item_code

// Consulta para obtener el nombre del producto
$item_name_query = "
    SELECT item_name
    FROM productos
    WHERE item_code = ?
    LIMIT 1
";

$stmt_name = $conn->prepare($item_name_query);
$stmt_name->bind_param("s", $item_code);
$stmt_name->execute();
$stmt_name->bind_result($item_name);
$stmt_name->fetch();
$stmt_name->close();

// Consulta para obtener el mayor productor en 2022 y su porcentaje
$top_producer_query = "
    WITH TotalProduction AS (
        SELECT SUM(CASE WHEN f.year = 2022 THEN f.value END) AS total_production
        FROM faowiki f
        WHERE f.item_code = ? 
            AND f.element_code = '5510'
            AND (f.area_code < 1000 OR f.area_code = 5000)
            AND f.area_code != 351
    ),
    RankedData AS (
        SELECT 
            CASE 
                WHEN p.nombre = 'República Democrática Popular de Etiopía' THEN 'Etiopía'
                WHEN p.nombre = 'República Democrática de Sudán' THEN 'Sudán'
                ELSE p.nombre
            END AS Pais,
            MAX(CASE WHEN f.year = 2022 THEN f.value END) AS `2022`,
            (SELECT total_production FROM TotalProduction) AS total_production
        FROM faowiki f
        JOIN paises p ON f.area_code = p.area_code
        LEFT JOIN (
            SELECT 'Sudán' AS nombre, 276 AS area_code
            UNION ALL
            SELECT 'Sudán' AS nombre, 206 AS area_code
            UNION ALL
            SELECT 'Etiopía' AS nombre, 238 AS area_code
            UNION ALL
            SELECT 'Etiopía' AS nombre, 62 AS area_code
        ) AS unified_paises ON p.nombre = unified_paises.nombre AND f.area_code = unified_paises.area_code
        WHERE f.item_code = ? 
            AND f.element_code = '5510'
            AND (f.area_code < 1000 OR f.area_code = 5000)
            AND f.area_code != 351
        GROUP BY 
            CASE 
                WHEN p.nombre = 'República Democrática Popular de Etiopía' THEN 'Etiopía'
                WHEN p.nombre = 'República Democrática de Sudán' THEN 'Sudán'
                ELSE p.nombre
            END
    )
    SELECT 
        Pais, 
        `2022`, 
        ROUND((`2022` / total_production) * 100, 2) AS percentage
    FROM RankedData
    ORDER BY `2022` DESC
    LIMIT 1;
";

$stmt_top_producer = $conn->prepare($top_producer_query);
$stmt_top_producer->bind_param("s", $item_code);
$stmt_top_producer->execute();
$result_top_producer = $stmt_top_producer->get_result();
$top_producer = $result_top_producer->fetch_assoc();
$stmt_top_producer->close();

$conn->close();

// Variables para el texto
$producto = htmlspecialchars(strtolower($item_name), ENT_QUOTES, 'UTF-8');
$año = 2022;
$toneladas = floatval($top_producer['`2022`']);
$pais = htmlspecialchars($top_producer['Pais'], ENT_QUOTES, 'UTF-8');
$porcentaje = htmlspecialchars($top_producer['percentage'], ENT_QUOTES, 'UTF-8');

// Generar el texto
if ($toneladas === 0) {
    echo "No se encontraron datos para la producción de $producto en $año.";
} else {
    echo "Esta es una lista histórica de países por producción de $producto, basada en los datos de la Organización de las Naciones Unidas para la Alimentación y la Agricultura. La producción mundial total de $producto en $año era de $toneladas toneladas. $pais es el mayor productor, representando el $porcentaje% de la producción mundial. Los territorios dependientes son mostrados en cursiva.";
}
?>
