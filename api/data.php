<?php
// api/data.php

// Incluir el archivo de configuración
require_once('../config.php');

// Parámetro de categoría
$categoria = $_GET['categoria'] ?? '';

// Mapeo de categorías basado en etiquetas o códigos
$categoria_map = [
    'area' => ["Area harvested", "5312"],
    'yield' => ["Yield", "5419"],
    'production' => ["Production Quantity", "5510"],
    'stocks' => ["Stocks", "5111"],
    'animals' => ["Producing Animals/Slaughtered", "5320"]
];

// Verificar si la categoría solicitada está en el mapeo
if (!isset($categoria_map[$categoria])) {
    // Si la categoría solicitada no está en el mapeo, devolver un error 400
    http_response_code(400);
    echo json_encode(["error" => "Categoría no válida"]);
    exit;
}

// Obtener la condición de la categoría
$condicion = $categoria_map[$categoria];
$label = $condicion[0];
$code = $condicion[1];

// Crear conexión a la base de datos desde config.php
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión a MySQL: " . $conn->connect_error);
}

// Consulta SQL para obtener datos según la categoría
$sql = "SELECT wikipedia_page, wikidata_item, item_code, item_name AS product_name, `update` AS update_required 
        FROM productos 
        WHERE label = ? OR code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $label, $code);
$stmt->execute();
$result = $stmt->get_result();

// Preparar resultado en formato JSON
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        "wikipedia_page" => $row['wikipedia_page'],
        "wikidata_item" => $row['wikidata_item'],
        "item_code" => $row['item_code'],
        "product_name" => $row['product_name'],
        "update" => (bool) $row['update_required']
    ];
}

// Devolver resultado como JSON
header('Content-Type: application/json');
echo json_encode($data);

// Cerrar conexión
$stmt->close();
$conn->close();
?>
