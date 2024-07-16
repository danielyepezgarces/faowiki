<?php
// api/data.php

// Incluir el archivo de configuración
require_once('../config.php');

// Parámetro de categoría
$categoria = $_GET['categoria'] ?? '';

// Crear conexión a la base de datos desde config.php
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión a MySQL: " . $conn->connect_error);
}

// Consulta SQL para obtener datos según la categoría
$sql = "SELECT wikipedia_page, wikidata_item, item_code, item_name AS product_name, `update` AS update_required 
        FROM productos 
        WHERE categoria = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $categoria);
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
