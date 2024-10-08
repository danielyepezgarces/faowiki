<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAOWIKI - Empowering Knowledge with FAO’s Global Data</title>
    <!-- Bootstrap CSS -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <style>

html, body {
    height: 100%;
    margin: 0;
    display: flex;
    flex-direction: column;
}

.container {
    flex: 1;
}

footer {
    background-color: #f8f9fa;
    padding: 20px 0;
    text-align: center;
    width: 100%;
}

        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .header h2 {
            font-size: 1.25rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        .sticky-header {
            position: fixed;
            top: 10px;
            right: 20px;
            z-index: 1000;
            background-color: #fff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .sticky-header a {
            color: #007bff;
            margin-right: 15px;
            text-decoration: none;
            font-weight: bold;
        }
        .product-item {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .product-item a {
            text-decoration: none;
            color: #212529;
        }

        footer p {
            margin: 0;
            font-size: 14px;
        }
        .product-links {
            font-size: 14px;
            white-space: nowrap;
        }
        .btn-group {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <!-- Sticky Header -->
    <div class="sticky-header">
        <a href="https://github.com/danielyepezgarces/faowiki" target="_blank">Source Code</a>
        <a href="#" target="_blank">Documentation</a>
    </div>

    <div class="container mt-5">
        <div class="header">
            <h1>FAOWIKI</h1>
            <h2>Empowering Knowledge with FAO’s Global Data</h2>
        </div>
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <h2 class="text-center mb-4">Lista de Productos</h2>
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
                            $item_name = ucfirst($row['item_name']); // Convertir la primera letra a mayúscula
                            $wikipedia_page = $row['wikipedia_page'];
                            $wikidata_item = $row['wikidata_item'];

                            // Reemplazar espacios con guiones bajos para la URL de Wikipedia
                            $wikipedia_page_encoded = str_replace(' ', '_', $wikipedia_page);

                            // Construir URL de Wikipedia con codificación UTF-8
                            $wikipedia_url = "https://es.wikipedia.org/wiki/" . urlencode($wikipedia_page_encoded);
                            $wikipedia_link = !empty($wikipedia_page) 
                                ? '<a class="btn btn-primary" href="' . $wikipedia_url . '" target="_blank">Wikipedia</a>'
                                : '<span class="btn btn-secondary disabled">Wikipedia (No disponible)</span>';

                            // Construir URL de Wikidata
                            $wikidata_url = "https://www.wikidata.org/wiki/" . urlencode($wikidata_item);
                            $wikidata_link = '<a class="btn btn-success" href="' . $wikidata_url . '" target="_blank">Wikidata</a>';

                            // Construir URL RAW
                            $raw_url = "/raw/tablas.php?item_code=" . urlencode($item_code);
                            $raw_link = '<a class="btn btn-info" href="' . $raw_url . '" target="_blank">RAW</a>';

                            // Enlace al detalle del producto
                            $product_url = "https://faowiki.toolforge.org/tablas.php?item_code=" . urlencode($item_code);

                            echo '<div class="product-item">';
                            echo '<h4><a href="' . $product_url . '">' . htmlspecialchars($item_name, ENT_QUOTES, 'UTF-8') . '</a></h4>';
                            echo '<div class="btn-group">' . $wikipedia_link . ' ' . $wikidata_link . ' ' . $raw_link . '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="product-item">No hay productos disponibles.</div>';
                    }

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
                    echo '<nav aria-label="Navegación de páginas"><ul class="pagination justify-content-center mt-4">';
                    for ($i = 1; $i <= $total_pages; $i++) {
                        echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
                    }
                    echo '</ul></nav>';

                    // Cerrar conexión
                    $conn->close();
                    ?>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 FAOWIKI - Developed by <a href="https://es.wikipedia.org/wiki/Usuario:Danielyepezgarces" target="_blank">Danielyepezgarces</a> - FAO data used under <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank">CC BY SA 4.0</a></p>
    </footer>

    <!-- Bootstrap JS y dependencias Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>
</html>
