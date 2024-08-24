<?php
include 'config.php';  // Conexión a la base de datos
include 'queries.php'; // Funciones SQL

$item_code = '1234'; // Ejemplo de código de item, deberías definir esto según tu lógica

// Obtener los datos de los países
$result = get_country_data($conn, $item_code);

// Obtener los datos totales
$result_total = get_total_data($conn, $item_code);

// Mostrar la tabla
echo "<table border='1' class='table table-striped'>
        <thead>
            <tr>
                <th>#</th> <!-- Nueva columna para el ranking -->
                <th>País</th>
                <th>1961</th>
                <th>1970</th>
                <th>1980</th>
                <th>1990</th>
                <th>2000</th>
                <th>2010</th>
                <th>2020</th>
                <th>2022</th>
            </tr>
        </thead>
        <tbody>";

$ranking = 1; // Contador para el ranking

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$ranking}</td>"; // Columna para el ranking
    echo "<td>" . htmlspecialchars($row['Pais'], ENT_QUOTES, 'UTF-8') . "</td>";

    foreach (['1961', '1970', '1980', '1990', '2000', '2010', '2020', '2022'] as $year) {
        $formatted = format_value($row[$year]);
        echo "<td data-sort-value='" . ($formatted['sort'] ?? '') . "'>" . $formatted['value'] . "</td>";
    }

    echo "</tr>";

    $ranking++; // Incrementar el ranking después de cada fila
}

// Mostrar los totales
if ($result_total->num_rows > 0) {
    while ($total_row = $result_total->fetch_assoc()) {
        echo "<tr class='table-footer'>";
        echo "<td></td>"; // Columna vacía para el ranking en la fila de total
        echo "<td>" . htmlspecialchars($total_row['Pais'], ENT_QUOTES, 'UTF-8') . "</td>";

        foreach (['1961', '1970', '1980', '1990', '2000', '2010', '2020', '2022'] as $year) {
            $formatted_total = format_value($total_row[$year]);
            echo "<td data-sort-value='" . ($formatted_total['sort'] ?? '') . "'>" . $formatted_total['value'] . "</td>";
        }

        echo "</tr>";
    }
}

echo "</tbody></table>";

$conn->close();
?>
