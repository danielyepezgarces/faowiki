<?php
function getHtmlTableFromUrl($url) {
    // Initialize cURL session
    $ch = curl_init();

    // Set the URL and other options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects if any

    // Execute cURL session and get the HTML content
    $htmlContent = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        return null;
    }

    // Close cURL session
    curl_close($ch);

    return $htmlContent;
}

function extractFirstTable($htmlContent) {
    // Load the HTML content into a DOMDocument
    $dom = new DOMDocument;
    @$dom->loadHTML($htmlContent);

    // Extract the first table element
    $table = $dom->getElementsByTagName('table')->item(0);

    if ($table) {
        // Save the table as a string
        return $dom->saveHTML($table);
    } else {
        // Return an error message if no table is found
        return '<p>No table found in the HTML content.</p>';
    }
}

function htmlTableToMediaWiki($htmlTable) {
    // Load the HTML table into a DOMDocument
    $dom = new DOMDocument;
    @$dom->loadHTML($htmlTable);
    
    // Initialize an empty MediaWiki table string
    $mediaWikiTable = "{| class=\"wikitable\"\n";

    // Loop through table rows
    foreach ($dom->getElementsByTagName('tr') as $row) {
        $mediaWikiTable .= "|-\n";
        
        // Loop through table cells
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

    // Close the MediaWiki table
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
