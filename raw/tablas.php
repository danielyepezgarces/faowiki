<?php
// Set UTF-8 encoding for the output
header('Content-Type: text/plain; charset=utf-8');

function getHtmlTableFromUrl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects if any
    
    // Set a User-Agent to mimic a request from a web browser
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    
    // Ensure the response is returned as UTF-8
    curl_setopt($ch, CURLOPT_ENCODING, ''); // Allows gzip encoding
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Charset: utf-8'));
    
    $htmlContent = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    // Convert the HTML content to UTF-8 if it's not already
    if (!mb_check_encoding($htmlContent, 'UTF-8')) {
        $htmlContent = mb_convert_encoding($htmlContent, 'UTF-8', 'auto');
    }

    return $htmlContent;
}

function extractContent($htmlContent) {
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $htmlContent); // Force UTF-8

    // Extract the first paragraph with the class 'entradilla'
    $paragraphs = $dom->getElementsByTagName('p');
    $entradilla = '';
    foreach ($paragraphs as $paragraph) {
        if ($paragraph->hasAttribute('class') && $paragraph->getAttribute('class') === 'entradilla') {
            $entradilla = trim($paragraph->textContent);
            break;
        }
    }

    // Extract the first table from the HTML content
    $tables = $dom->getElementsByTagName('table');
    if ($tables->length > 0) {
        $table = $tables->item(0);
        $tableHtml = $dom->saveHTML($table);
    } else {
        $tableHtml = '<p>No table found in the HTML content.</p>';
    }

    return ['entradilla' => $entradilla, 'tableHtml' => $tableHtml];
}

function htmlTableToMediaWiki($htmlTable) {
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $htmlTable); // Force UTF-8

    $mediaWikiTable = "{| class=\"wikitable sortable\"\n"; // Add sortable class

    $rows = $dom->getElementsByTagName('tr');
    if ($rows->length === 0) {
        return "<p>No rows found in the HTML table.</p>";
    }

    $totalRowIndex = -1;

    // Find the index of the total row
    foreach ($rows as $index => $row) {
        foreach ($row->childNodes as $cell) {
            if ($cell->nodeType === XML_ELEMENT_NODE) {
                $cellText = trim($cell->textContent);
                if (strtolower($cellText) === "total") {
                    $totalRowIndex = $index;
                    break 2; // Exit both loops
                }
            }
        }
    }

    // Process rows
    foreach ($rows as $index => $row) {
        if ($index === $totalRowIndex) {
            // Add the total row with the sortbottom class
            $mediaWikiTable .= "|-\n";
            $mediaWikiTable .= "| class=\"sortbottom\" | \n"; // Empty cell for the sortbottom class
            foreach ($row->childNodes as $cell) {
                if ($cell->nodeType === XML_ELEMENT_NODE) {
                    $cellText = trim($cell->textContent);
                    $mediaWikiTable .= "| " . $cellText . "\n";
                }
            }
            $mediaWikiTable .= "|}\n";
        } else {
            // Process regular rows
            $mediaWikiTable .= "|-\n";
            foreach ($row->childNodes as $cell) {
                if ($cell->nodeType === XML_ELEMENT_NODE) {
                    $cellText = trim($cell->textContent);
                    $sortValue = $cell->hasAttribute('data-sort-value') ? $cell->getAttribute('data-sort-value') : null;
                    $sortAttribute = $sortValue ? " data-sort-value=\"" . htmlspecialchars($sortValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\"" : "";

                    if ($cell->tagName === 'th') {
                        $mediaWikiTable .= "! " . $sortAttribute . " " . $cellText . "\n";
                    } elseif ($cell->tagName === 'td') {
                        if ($sortAttribute) {
                            $mediaWikiTable .= "| " . $sortAttribute . " | " . $cellText . "\n";
                        } else {
                            $mediaWikiTable .= "| " . $cellText . "\n";
                        }
                    }
                }
            }
        }
    }

    // Append closing brace for the table
    if ($totalRowIndex === -1) {
        $mediaWikiTable .= "|}\n"; // Close table if no total row found
    }

    return $mediaWikiTable;
}


// Get the item_code from the URL query parameter
$itemCode = isset($_GET['item_code']) ? $_GET['item_code'] : '';

if ($itemCode) {
    $url = "https://faowiki.toolforge.org/tablas.php?item_code=$itemCode";

    $htmlContent = getHtmlTableFromUrl($url);

    if ($htmlContent) {
        $content = extractContent($htmlContent);

        $entradilla = $content['entradilla'];
        $htmlTable = $content['tableHtml'];

        $mediaWikiTable = htmlTableToMediaWiki($htmlTable);
        
        // Add the entradilla content before the table
        echo $entradilla . "\n\n== Tabla ==\n" . $mediaWikiTable . "\n\n== Referencias ==\n{{listaref}}\n\n[[Categoría:Anexos:Producción mundial de alimentos]]";
    } else {
        echo "Failed to retrieve HTML content from the URL.";
    }
} else {
    echo "item_code parameter is missing in the URL.";
}
?>
