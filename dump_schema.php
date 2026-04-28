<?php
include 'db.php';

$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    echo "\n-- Table: $table\n";
    $createResult = $conn->query("SHOW CREATE TABLE `$table` ");
    if ($createResult) {
        $createRow = $createResult->fetch_assoc();
        echo $createRow['Create Table'] . ";\n";
    }
}
?>