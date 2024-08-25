<?php
include 'query_functions.php';

// Fetch additional data for the text
$lastyear = 2022; // Replace with actual value
$toneladas = get_total_production($conn, $item_code, $lastyear); // Replace with actual function
$highest_country = get_highest_producer($conn, $item_code, $lastyear); // Replace with actual function
$percentage_highest_producer = get_highest_producer_percentage($conn, $item_code, $lastyear); // Replace with actual function
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/tables.css" rel="stylesheet">
</head>
<body>
    <div class="content">
        <h1><?php echo "Histórico producción mundial de " . htmlspecialchars(strtolower($item_name), ENT_QUOTES, 'UTF-8'); ?></h1>
        <p>Esta es una lista histórica de países por producción de <?php echo htmlspecialchars($item_name, ENT_QUOTES, 'UTF-8'); ?>, basada en los datos de la Organización de las Naciones Unidas para la Alimentación y la Agricultura. La producción mundial total de <?php echo htmlspecialchars($item_name, ENT_QUOTES, 'UTF-8'); ?> en <?php echo $lastyear; ?> era de <?php echo $toneladas; ?> toneladas. <?php echo htmlspecialchars($highest_country, ENT_QUOTES, 'UTF-8'); ?> es el mayor productor, representando el <?php echo $percentage_highest_producer; ?>% de la producción mundial. Los territorios dependientes son mostrados en cursiva.</p>
        <div class="table-container">
            <?php
            echo "<table border='1' class='table table-striped'>
                    <thead>
                        <tr>
                            <th>#</th>
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

            $ranking = 1;

            while ($row = $data->fetch_assoc()) {
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

            if ($total->num_rows > 0) {
                $total_row = $total->fetch_assoc();
                echo "<tr class='table-footer'>
                        <td></td>
                        <td>" . htmlspecialchars($total_row['Pais'], ENT_QUOTES, 'UTF-8') . "</td>";

                foreach ($years as $year) {
                    $formatted_value = format_value($total_row[$year] ?? '');
                    echo "<td style='text-align:right; white-space: nowrap;'>" . htmlspecialchars($formatted_value['value'], ENT_QUOTES, 'UTF-8') . "</td>";
                }

                echo "</tr>";
            }

            echo "</tbody></table>";
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
