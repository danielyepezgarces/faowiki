<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getHtmlTableFromUrl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects if any
    $htmlContent = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        curl_close($ch);
        return null;
    }

    curl_close($ch);
    return $htmlContent;
}

function extractFirstTable($htmlContent) {
    $dom = new DOMDocument;
    @$dom->loadHTML($htmlContent);

    // Debug: Output the full HTML content
    echo "<pre>" . htmlspecialchars($htmlContent) . "</pre>";

    $table = $dom->getElementsByTagName('table')->item(0);

    if ($table) {
        return $dom->saveHTML($table);
    } else {
        return '<p>No table found in the HTML content.</p>';
    }
}

function htmlTableToMediaWiki($htmlTable) {
    $dom = new DOMDocument;
    @$dom->loadHTML($htmlTable);

    $mediaWikiTable = "{| class=\"wikitable\"\n";

    $rows = $dom->getElementsByTagName('tr');
    if ($rows->length === 0) {
        return "<p>No rows found in the HTML table.</p>";
    }

    foreach ($rows as $row) {
        $mediaWikiTable .= "|-\n";

        foreach ($row->childNodes as $cell) {
            if ($cell->nodeType === XML_ELEMENT_NODE) {
                $cellText = trim($cell->textContent);
                if ($cell->tagName === 'th') {
                    $mediaWikiTable .= "! $cellText\n";
                } elseif ($cell->tagName === 'td') {
                    $mediaWikiTable .= "| $cellText\n";
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
