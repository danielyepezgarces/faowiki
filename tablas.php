<?php
include 'db_connect.php';
include 'query_functions.php';

$item_code = isset($_GET['item_code']) ? $_GET['item_code'] : '221';

$item_name = get_item_name($conn, $item_code);
$page_title = "Histórico producción mundial de " . htmlspecialchars(strtolower($item_name), ENT_QUOTES, 'UTF-8') . " | FAOWIKI";

$data = get_data($conn, $item_code);
$total = get_total($conn, $item_code);

include 'template.php';

$conn->close();
?>
