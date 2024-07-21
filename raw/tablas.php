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

function extractFirstTable($htmlContent) {
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $htmlContent); // Force UTF-8

    $tables = $dom->getElementsByTagName('table');
    if ($tables->length > 0) {
        $table = $tables->item(0);
        return $dom->saveHTML($table);
    } else {
        return '<p>No table found in the HTML content.</p>';
    }
}

function htmlTableToMediaWiki($htmlTable) {
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $htmlTable); // Force UTF-8

    $mediaWikiTable = "{| class=\"wikitable sortable\"\n"; // Add sortable class

    $rows = $dom->getElementsByTagName('tr');
    if ($rows->length === 0) {
        return "<p>No rows found in the HTML table.</p>";
    }

    foreach ($rows as $row) {
        $mediaWikiTable .= "|-\n";

        foreach ($row->childNodes as $cell) {
            if ($cell->nodeType === XML_ELEMENT_NODE) {
                $cellText = trim($cell->textContent);
                $sortValue = $cell->hasAttribute('data-sort-value') ? $cell->getAttribute('data-sort-value') : null;
                $sortAttribute = $sortValue ? " data-sort-value=\"" . htmlspecialchars($sortValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\"" : "";

                if ($cell->tagName === 'th') {
                    $mediaWikiTable .= "! " . ($sortAttribute ? $sortAttribute . " " : "") . htmlspecialchars($cellText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
                } elseif ($cell->tagName === 'td') {
                    $mediaWikiTable .= "| " . ($sortAttribute ? $sortAttribute . " " : "") . htmlspecialchars($cellText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
                }
            }
        }
    }

    $mediaWikiTable .= "|}";
    return $mediaWikiTable;
}

// Get the item_code from the URL query parameter
$itemCode = isset($_GET['item_code']) ? $_GET['item_code'] : '';

if ($itemCode) {
    // Construct the URL with the item_code
    $url = "https://faowiki.toolforge.org/tablas.php?item_code=$itemCode";

    // Get the HTML content from the URL
    $htmlContent = getHtmlTableFromUrl($url);

    if ($htmlContent) {
        // Extract the first table from the HTML content
        $htmlTable = extractFirstTable($htmlContent);

        // Debug: Output the HTML table for verification
        // echo "<pre>" . htmlspecialchars($htmlTable) . "</pre>";

        // Convert the HTML table to MediaWiki format
        $mediaWikiTable = htmlTableToMediaWiki($htmlTable);
        echo $mediaWikiTable;
    } else {
        echo "Failed to retrieve HTML content from the URL.";
    }
} else {
    echo "item_code parameter is missing in the URL.";
}
?>
