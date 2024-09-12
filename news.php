<?php
// Obtén la categoría desde la URL, con un valor predeterminado si no se proporciona
$category = isset($_GET['category']) ? urlencode($_GET['category']) : 'Bogotá';

// Construye la URL de la página de la categoría
$url = 'https://es.wikinews.org/wiki/Categor%C3%ADa:' . $category;

// Inicializa una sesión cURL
$ch = curl_init();

// Configura la URL y otras opciones
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Ejecuta la sesión cURL y obtiene el contenido HTML
$html = curl_exec($ch);

// Cierra la sesión cURL
curl_close($ch);

// Verifica si se obtuvo el HTML correctamente
if ($html === false) {
    die('Error al obtener el contenido de la página.');
}

// Carga el HTML en DOMDocument
$dom = new DOMDocument;
@$dom->loadHTML($html);

// Encuentra el nodo <td class="catcontent">
$xpath = new DOMXPath($dom);
$nodes = $xpath->query('//td[@class="catcontent"]//ul/li');

// Extrae y muestra el contenido de las noticias
if ($nodes->length > 0) {
    echo '<ul>';
    foreach ($nodes as $node) {
        // Extrae la fecha
        $dateNode = $xpath->query('.//text()[1]', $node)->item(0);
        $date = $dateNode ? trim($dateNode->nodeValue) : 'No Date';

        // Extrae el título y el enlace
        $titleNode = $xpath->query('.//a', $node)->item(0);
        $title = $titleNode ? $titleNode->nodeValue : 'No Title';
        $link = $titleNode ? 'https://es.wikinews.org' . $titleNode->getAttribute('href') : '#';

        // Muestra el resultado
        echo '<li>';
        echo '<strong>' . htmlspecialchars($date) . '</strong>: ';
        echo '<a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($title) . '</a>';
        echo '</li>';
    }
    echo '</ul>';
} else {
    echo 'No se encontraron noticias para la categoría especificada.';
}
?>
