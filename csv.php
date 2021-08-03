<?php
require "connect.php";

$week = getWeek();
$dataTable = getTable($week, false, false);

// Write the elements type
$columns = count($dataTable[0]) - 1;
$secondLine = ["string"];
for ($i=0; $i < $columns; $i++) {
  array_push($secondLine, "number");
}

// Add the elements type row
$firstLine = array_shift($dataTable);
array_unshift($dataTable, $firstLine, $secondLine);

// Join rows with commas
for ($i=0; $i < count($dataTable); $i++) {
  $dataTable[$i] = join(",", $dataTable[$i]);
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="AssetsDownloads.csv"');
echo join("\n", $dataTable);
?>
