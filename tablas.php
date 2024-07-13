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

    $page_title = "Histórico producción mundial de " . htmlspecialchars(strtolower($item_name)) . " | FAOWIKI";
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
    </style>
</head>
<body>
    <div class="content">
        <h1><?php echo "Histórico producción mundial de " . htmlspecialchars(strtolower($item_name)); ?></h1>

        <div class="table-container">
            <?php
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
    f.item
FROM faowiki f
JOIN paises p ON f.area_code = p.area_code
WHERE f.item_code = ? 
    AND f.element_code = '5510'
    AND (f.area_code = 276 OR f.area_code = 206 OR f.area_code = 238 OR f.area_code = 62) -- Filtrar por los códigos de área relevantes
    AND (f.area_code < 1000 OR f.area_code = 5000)
    AND f.area_code != 351
GROUP BY 
    CASE 
        WHEN p.nombre = 'República Democrática Popular de Etiopía' THEN 'Etiopía'
        WHEN p.nombre = 'República Democrática de Sudán' THEN 'Sudán'
        ELSE p.nombre
    END,
    f.item
ORDER BY p.id;

            ";

            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die('Error en la preparación de la consulta: ' . htmlspecialchars($conn->error));
            }

            $stmt->bind_param("s", $item_code);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result === false) {
                die('Error al ejecutar la consulta: ' . htmlspecialchars($stmt->error));
            }

            if ($result->num_rows > 0) {
                echo "<table border='1' class='table table-striped'>
                        <thead>
                            <tr>
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
                                return '-';
                            }
                            $value = str_replace(',', '', $value);  // Remover comas si existen
                            $value = floatval($value) / 1000;
                        
                            if ($value < 0.1) {
                                return '<0.1';
                            } elseif ($value < 1) {
                                return number_format($value, 1, '.', ''); // Un decimal
                            } elseif ($value >= 1 && $value < 10000) {
                                return number_format($value, 0, '.', ''); // Sin decimales y sin separador de miles
                            } else {
                                return number_format($value, 0, '.', ' '); // Sin decimales, con espacio como separador de miles
                            }
                        }                        

                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>";

                    switch ($row['Pais']) {
                        case 'Bélgica-Luxemburgo':
                            echo '{{Bandera|Bélgica}}{{Bandera|Luxemburgo}} [[Unión Económica Belgo-Luxemburguesa|' . htmlspecialchars(trim($row['Pais'])) . ']]';
                            break;
                        case 'Total':
                            echo htmlspecialchars(trim($row['Pais']));
                            break;
                        default:
                            echo '{{Bandera2|' . htmlspecialchars(trim($row['Pais'])) . '}}';
                            break;
                    }

                    echo "</td>
                            <td style='text-align:right; white-space: nowrap;'>" . htmlspecialchars(format_value($row['1961'] ?? '')) . "</td>
                            <td style='text-align:right; white-space: nowrap;'>" . htmlspecialchars(format_value($row['1970'] ?? '')) . "</td>
                            <td style='text-align:right; white-space: nowrap;'>" . htmlspecialchars(format_value($row['1980'] ?? '')) . "</td>
                            <td style='text-align:right; white-space: nowrap;'>" . htmlspecialchars(format_value($row['1990'] ?? '')) . "</td>
                            <td style='text-align:right; white-space: nowrap;'>" . htmlspecialchars(format_value($row['2000'] ?? '')) . "</td>
                            <td style='text-align:right; white-space: nowrap;'>" . htmlspecialchars(format_value($row['2010'] ?? '')) . "</td>
                            <td style='text-align:right; white-space: nowrap;'>" . htmlspecialchars(format_value($row['2020'] ?? '')) . "</td>
                            <td style='text-align:right; white-space: nowrap;'>" . htmlspecialchars(format_value($row['2022'] ?? '')) . "</td>
                        </tr>";
                }
                echo "</tbody></table>";
            } else {
                echo "<p>No se encontraron resultados para el Item Code: " . htmlspecialchars($item_code) . "</p>";
            }

            $stmt->close();
            $conn->close();
            ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 FAOWIKI - Developed by <a href="https://es.wikipedia.org/wiki/Usuario:Danielyepezgarces" target="_blank">Danielyepezgarces</a> - FAO data used under CC BY SA</p>
    </footer>

    <!-- Bootstrap JS y dependencias Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>
</html>
