<?php
require "connect.php";

$rawdata = getWeek();

header('Content-Type: application/json');
echo json_encode($rawdata);
?>
