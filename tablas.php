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

    // Consulta para obtener el mayor productor en 2022 y su porcentaje
    $top_producer_query = "
        WITH RankedData AS (
            SELECT 
                CASE 
                    WHEN p.nombre = 'República Democrática Popular de Etiopía' THEN 'Etiopía'
                    WHEN p.nombre = 'República Democrática de Sudán' THEN 'Sudán'
                    ELSE p.nombre
                END AS Pais,
                MAX(CASE WHEN f.year = 2022 THEN f.value END) AS `2022`,
                (SELECT SUM(CASE WHEN f.year = 2022 THEN f.value END) FROM faowiki f WHERE f.item_code = ? AND f.element_code = '5510' AND (f.area_code < 1000 OR f.area_code = 5000) AND f.area_code != 351) AS total_production
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
    $stmt_top_producer->bind_param("ss", $item_code, $item_code);
    $stmt_top_producer->execute();
    $result_top_producer = $stmt_top_producer->get_result();
    $top_producer = $result_top_producer->fetch_assoc();
    $stmt_top_producer->close();
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
        <h1><?php echo "Histórico producción mundial de " . htmlspecialchars(strtolower($item_name), ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php
        // Mostrar el texto dinámicamente
        if ($top_producer) {
            $producto = htmlspecialchars(strtolower($item_name), ENT_QUOTES, 'UTF-8');
            $año = 2022;
            $toneladas = number_format(floatval($top_producer['`2022`']) / 1000, 0, '.', ',');
            $pais = htmlspecialchars($top_producer['Pais'], ENT_QUOTES, 'UTF-8');
            $porcentaje = htmlspecialchars($top_producer['percentage'], ENT_QUOTES, 'UTF-8');

            echo "<p>Esta es una lista histórica de países por producción de $producto, basada en los datos de la Organización de las Naciones Unidas para la Alimentación y la Agricultura. La producción mundial total de $producto en $año era de $toneladas toneladas. $pais es el mayor productor, representando el $porcentaje% de la producción mundial. Los territorios dependientes son mostrados en cursiva.</p>";
        }
        ?>

        <div class="table-container">
            <?php
            $sql = "
            WITH RankedData AS (
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
                    f.item,
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
                GROUP BY 
                    CASE 
                        WHEN p.nombre = 'República Democrática Popular de Etiopía' THEN 'Etiopía'
                        WHEN p.nombre = 'República Democrática de Sudán' THEN 'Sudán'
                        ELSE p.nombre
                    END,
                    f.item
            )
            SELECT * FROM RankedData
            ORDER BY ranking_2022;
            ";

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

            if ($result->num_rows > 0) {
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

                $rank = 1; // Inicializar el contador de ranking
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>" . $rank . "</td> <!-- Mostrar ranking -->
                            <td>" . htmlspecialchars($row['Pais'], ENT_QUOTES, 'UTF-8') . "</td>
                            <td>" . number_format($row['1961'], 0, '.', ',') . "</td>
                            <td>" . number_format($row['1970'], 0, '.', ',') . "</td>
                            <td>" . number_format($row['1980'], 0, '.', ',') . "</td>
                            <td>" . number_format($row['1990'], 0, '.', ',') . "</td>
                            <td>" . number_format($row['2000'], 0, '.', ',') . "</td>
                            <td>" . number_format($row['2010'], 0, '.', ',') . "</td>
                            <td>" . number_format($row['2020'], 0, '.', ',') . "</td>
                            <td>" . number_format($row['2022'], 0, '.', ',') . "</td>
                        </tr>";
                    $rank++; // Incrementar el contador de ranking
                }
                
                echo "</tbody>
                    </table>";
            } else {
                echo "<p>No se encontraron datos.</p>";
            }

            $stmt->close();
            $conn->close();
            ?>
        </div>
    </div>

    <footer>
        <p>© 2024 FAOWIKI - Desarrollado por Danielyepezgarces (usuario de Wikipedia en español) - Datos de la FAO bajo CC BY SA.</p>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>
</html>
