<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Productos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .product-item {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .product-item a {
            text-decoration: none; /* Eliminar subrayado de los enlaces */
            color: #212529; /* Color de texto para los enlaces */
        }
        footer {
            background-color: #f8f9fa;
            padding: 20px 0;
            text-align: center;
            position: fixed;
            width: 100%;
            bottom: 0;
        }
        footer p {
            margin: 0;
            font-size: 14px;
        }
        .product-links {
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <h1 class="text-center mb-4">Lista de Productos</h1>
                <div class="list-group">
                    <?php
                    include 'config.php';

                    // Crear conexión
                    $conn = new mysqli($servername, $username, $password, $dbname);

                    // Verificar conexión
                    if ($conn->connect_error) {
                        die("Conexión fallida: " . $conn->connect_error);
                    }

                    // Configurar el conjunto de caracteres a UTF-8
                    $conn->set_charset("utf8mb4");

                    // Calcular el offset según la página actual
                    $page = isset($_GET['page']) ? $_GET['page'] : 1;
                    $results_per_page = 10; // Cantidad de resultados por página
                    $offset = ($page - 1) * $results_per_page;

                    // Consulta SQL para obtener los nombres únicos de los productos y sus códigos paginados
                    $sql = "
                        SELECT item_name, item_code, wikipedia_page, wikidata_item
                        FROM productos
                        WHERE categoria = '5510'
                          AND wikipedia_page IS NOT NULL AND TRIM(wikipedia_page) != ''
                          AND wikidata_item IS NOT NULL AND TRIM(wikidata_item) != ''
                        ORDER BY item_name
                        LIMIT $offset, $results_per_page
                    ";

                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        // Mostrar cada nombre de producto como un enlace a tablas.php con el item_code
                        while ($row = $result->fetch_assoc()) {
                            $item_code = $row['item_code'];
                            $item_name = $row['item_name'];
                            $wikipedia_page = $row['wikipedia_page'];
                            $wikidata_item = $row['wikidata_item'];

                            // Construir URL de Wikipedia con codificación UTF-8
                            $wikipedia_url = "https://es.wikipedia.org/wiki/" . urlencode($wikipedia_page);
                            $wikipedia_link = !empty($wikipedia_page) 
                                ? '<a href="' . $wikipedia_url . '" target="_blank">Wikipedia</a>'
                                : 'Wikipedia (No disponible)';

                            // Construir URL de Wikidata
                            $wikidata_url = "https://www.wikidata.org/wiki/" . urlencode($wikidata_item);
                            $wikidata_link = '<a href="' . $wikidata_url . '" target="_blank">Wikidata</a>';

                            // Enlace al detalle del producto
                            $product_url = "https://faowiki.toolforge.org/tablas.php?item_code=" . urlencode($item_code);

                            echo '<div class="product-item">';
                            echo '<h4><a href="' . $product_url . '">' . htmlspecialchars($item_name, ENT_QUOTES, 'UTF-8') . '</a></h4>';
                            echo '<p class="product-links">' . $wikipedia_link . ' - ' . $wikidata_link . '</p>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="product-item">No hay productos disponibles.</div>';
                    }

                    // Cerrar conexión
                    $conn->close();
                    ?>
                </div>
                <nav aria-label="Navegación de páginas">
                    <ul class="pagination justify-content-center mt-4">
                        <?php
                        // Calcular el número total de páginas
                        $sql_count = "
                            SELECT COUNT(*) AS total_count
                            FROM productos
                            WHERE categoria = '5510'
                              AND wikipedia_page IS NOT NULL AND TRIM(wikipedia_page) != ''
                              AND wikidata_item IS NOT NULL AND TRIM(wikidata_item) != ''
                        ";
                        $result_count = $conn->query($sql_count);
                        $total_count = $result_count->fetch_assoc()['total_count'];
                        $total_pages = ceil($total_count / $results_per_page);

                        // Mostrar enlaces de paginación
                        for ($i = 1; $i <= $total_pages; $i++) {
                            echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
                        }
                        ?>
                    </ul>
                </nav>
            </div>
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
