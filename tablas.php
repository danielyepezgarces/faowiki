<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    include 'config.php';

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

    $page_title = "Histórico producción mundial de " . htmlspecialchars(strtolower($item_name), ENT_QUOTES, 'UTF-8') . " | FAOWIKI";
    echo "<title>$page_title</title>";
    ?>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center; /* Centrar el contenido principal */
        }
        .table-container {
            width: 80%;
            margin: 0 auto; /* Centrar la tabla */
        }
        h1 {
            text-align: center; /* Centrar el header */
            margin: 25px 0; /* Añadir margen superior e inferior */
        }
        footer {
            background-color: #f8f9fa;
            padding: 20px 0;
            text-align: center;
            width: 100%;
        }
        footer p {
            margin: 0;
            font-size: 14px;
        }
        .table-footer {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="content">
        <h1><?php echo "Histórico producción mundial de " . htmlspecialchars(strtolower($item_name), ENT_QUOTES, 'UTF-8'); ?></h1>

        <div class="table-container">
            <?php
            // Consulta para obtener los datos de los países (sin incluir "Total")
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
                AND p.nombre != 'Total'
            GROUP BY 
                CASE 
                    WHEN p.nombre = 'República Democrática Popular de Etiopía' THEN 'Etiopía'
                    WHEN p.nombre = 'República Democrática de Sudán' THEN 'Sudán'
                    ELSE p.nombre
                END
            ORDER BY ranking_2022;
            ";

            // Consulta para obtener la fila de "Total"
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
                AND (f.area_code < 1000 OR f.area_code = 5000)
                AND f.area_code != 351;
            ";

            // Ejecutar la consulta para los datos de los países
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die('Error en la preparación de la consulta: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
            }

            $stmt->bind_param("s", $item_code);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result === false) {
                die('Error al ejecutar la consulta: ' . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8'));
            }

            // Ejecutar la consulta para el total
            $stmt_total = $conn->prepare($total_sql);
            if ($stmt_total === false) {
                die('Error en la preparación de la consulta de total: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
            }

            $stmt_total->bind_param("s", $item_code);
            $stmt_total->execute();
            $result_total = $stmt_total->get_result();

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

            $ranking = 1; // Contador para el ranking

            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . htmlspecialchars($ranking++, ENT_QUOTES, 'UTF-8') . "</td>
                        <td>";

                switch ($row['Pais']) {
                    case 'Bélgica-Luxemburgo':
                        echo '{{Bandera|Bélgica}}{{Bandera|Luxemburgo}} [[Unión Económica Belgo-Luxemburguesa|' . htmlspecialchars(trim($row['Pais']), ENT_QUOTES, 'UTF-8') . ']]';
                        break;
                    default:
                        echo '{{Bandera2|' . htmlspecialchars(trim($row['Pais']), ENT_QUOTES, 'UTF-8') . '}}';
                        break;
                }

                echo "</td>";

                $years = ['1961', '1970', '1980', '1990', '2000', '2010', '2020', '2022'];
                foreach ($years as $year) {
                    $formatted_value = format_value($row[$year] ?? '');
                    $sort_attribute = $formatted_value['sort'] ? " data-sort-value=\"" . htmlspecialchars($formatted_value['sort'], ENT_QUOTES, 'UTF-8') . "\"" : "";
                    echo "<td style='text-align:right; white-space: nowrap;'{$sort_attribute}>" . htmlspecialchars($formatted_value['value'], ENT_QUOTES, 'UTF-8') . "</td>";
                }

                echo "</tr>";
            }

            // Mostrar la fila 'Total' si existe
            if ($result_total->num_rows > 0) {
                $total_row = $result_total->fetch_assoc();
                echo "<tr class='table-footer'>
                        <td></td> <!-- Columna de ranking vacía para la fila 'Total' -->
                        <td>" . htmlspecialchars($total_row['Pais'], ENT_QUOTES, 'UTF-8') . "</td>";

                foreach ($years as $year) {
                    $formatted_value = format_value($total_row[$year] ?? '');
                    echo "<td style='text-align:right; white-space: nowrap;'>" . htmlspecialchars($formatted_value['value'], ENT_QUOTES, 'UTF-8') . "</td>";
                }

                echo "</tr>";
            }

            echo "</tbody></table>";

            // Cierre de conexiones
            $stmt->close();
            $stmt_total->close();
            $conn->close();
            ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> FAOWIKI. Todos los derechos reservados.</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
