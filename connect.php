<?php
require "config.php";

// Create connection
$dbh = new mysqli($host, $user, $password);

// Check connection
if ($dbh->connect_error) {
  die("Connection failed: " . $dbh->connect_error);
}

if(! $dbh->select_db($database)) {
  die("Database not found");
}

// Check the password
$code = isset($_GET["code"]) ? $_GET["code"] : "";
if($password != $code) {
  die("Password missing/wrong.");
}


function getWeek() {
  global $dbh;

  $query = "SELECT a.filename AS 'name',
    d8.dl_count AS '8 days ago',
    d7.dl_count AS '7 days ago',
    d6.dl_count AS '6 days ago',
    d5.dl_count AS '5 days ago',
    d4.dl_count AS '4 days ago',
    d3.dl_count AS '3 days ago',
    d2.dl_count AS '2 days ago',
    d1.dl_count AS '1 day ago'
    FROM project_asset a,
    (SELECT * FROM project_downloads WHERE log_date = DATE_SUB(CURDATE(), INTERVAL 1 day))d1,
    (SELECT * FROM project_downloads WHERE log_date = DATE_SUB(CURDATE(), INTERVAL 2 day))d2,
    (SELECT * FROM project_downloads WHERE log_date = DATE_SUB(CURDATE(), INTERVAL 3 day))d3,
    (SELECT * FROM project_downloads WHERE log_date = DATE_SUB(CURDATE(), INTERVAL 4 day))d4,
    (SELECT * FROM project_downloads WHERE log_date = DATE_SUB(CURDATE(), INTERVAL 5 day))d5,
    (SELECT * FROM project_downloads WHERE log_date = DATE_SUB(CURDATE(), INTERVAL 6 day))d6,
    (SELECT * FROM project_downloads WHERE log_date = DATE_SUB(CURDATE(), INTERVAL 7 day))d7,
    (SELECT * FROM project_downloads WHERE log_date = DATE_SUB(CURDATE(), INTERVAL 8 day))d8
    WHERE d1.idasset = a.idasset
    AND d2.idasset = a.idasset
    AND d3.idasset = a.idasset
    AND d4.idasset = a.idasset
    AND d5.idasset = a.idasset
    AND d6.idasset = a.idasset
    AND d7.idasset = a.idasset
    AND d8.idasset = a.idasset";

  $result = $dbh->query($query);
  if(!$result) {
    die("Error retrieving projects data");
  }

  return $result->fetch_all(MYSQLI_ASSOC);
}

function getTotals() {
  global $dbh;

  $query = "SELECT p.name AS 'name',
    SUM(d8.dl_count) AS '8 days ago',
    SUM(d7.dl_count) AS '7 days ago',
    SUM(d6.dl_count) AS '6 days ago',
    SUM(d5.dl_count) AS '5 days ago',
    SUM(d4.dl_count) AS '4 days ago',
    SUM(d3.dl_count) AS '3 days ago',
    SUM(d2.dl_count) AS '2 days ago',
    SUM(d1.dl_count) AS '1 day ago'
    FROM project_asset a, project p,
    (SELECT * FROM project_downloads WHERE log_date = DATE_SUB(CURDATE(), INTERVAL 1 day))d1,
    (SELECT * FROM project_downloads WHERE log_date = DATE_SUB(CURDATE(), INTERVAL 2 day))d2,
    (SELECT * FROM project_downloads WHERE log_date = DATE_SUB(CURDATE(), INTERVAL 3 day))d3,
    (SELECT * FROM project_downloads WHERE log_date = DATE_SUB(CURDATE(), INTERVAL 4 day))d4,
    (SELECT * FROM project_downloads WHERE log_date = DATE_SUB(CURDATE(), INTERVAL 5 day))d5,
    (SELECT * FROM project_downloads WHERE log_date = DATE_SUB(CURDATE(), INTERVAL 6 day))d6,
    (SELECT * FROM project_downloads WHERE log_date = DATE_SUB(CURDATE(), INTERVAL 7 day))d7,
    (SELECT * FROM project_downloads WHERE log_date = DATE_SUB(CURDATE(), INTERVAL 8 day))d8
    WHERE a.idproject = p.idproject
    AND d1.idasset = a.idasset
    AND d2.idasset = a.idasset
    AND d3.idasset = a.idasset
    AND d4.idasset = a.idasset
    AND d5.idasset = a.idasset
    AND d6.idasset = a.idasset
    AND d7.idasset = a.idasset
    AND d8.idasset = a.idasset
    GROUP BY a.idproject";

  $result = $dbh->query($query);
  if(!$result) {
    die("Error retrieving projects data");
  }

  return $result->fetch_all(MYSQLI_ASSOC);
}

function getTable($rawData, $daily = true, $incrementsOnly = false) {
  $dataTable = [
    ['Date'],
    ['7 days ago'],
    ['6 days ago'],
    ['5 days ago'],
    ['4 days ago'],
    ['3 days ago'],
    ['2 days ago'],
    ['1 day ago']
  ];

  for ($i=0; $i < count($rawData); $i++) {
    if(!$incrementsOnly || ($incrementsOnly && $rawData[$i]["1 day ago"] - $rawData[$i]["8 days ago"] > 0)) {
      array_push($dataTable[0], $rawData[$i]["name"]);
      array_push($dataTable[1], $daily ? ($rawData[$i]["7 days ago"] - $rawData[$i]["8 days ago"]) : (int)$rawData[$i]["7 days ago"]);
      array_push($dataTable[2], $daily ? ($rawData[$i]["6 days ago"] - $rawData[$i]["7 days ago"]) : (int)$rawData[$i]["6 days ago"]);
      array_push($dataTable[3], $daily ? ($rawData[$i]["5 days ago"] - $rawData[$i]["6 days ago"]) : (int)$rawData[$i]["5 days ago"]);
      array_push($dataTable[4], $daily ? ($rawData[$i]["4 days ago"] - $rawData[$i]["5 days ago"]) : (int)$rawData[$i]["4 days ago"]);
      array_push($dataTable[5], $daily ? ($rawData[$i]["3 days ago"] - $rawData[$i]["4 days ago"]) : (int)$rawData[$i]["3 days ago"]);
      array_push($dataTable[6], $daily ? ($rawData[$i]["2 days ago"] - $rawData[$i]["3 days ago"]) : (int)$rawData[$i]["2 days ago"]);
      array_push($dataTable[7], $daily ? ($rawData[$i]["1 day ago"] - $rawData[$i]["2 days ago"]) : (int)$rawData[$i]["1 day ago"]);
    }
  }

  return $dataTable;
}

function getWeekByOS($daily = true) {
  $rawData = getWeek();

  $dataTable = [
  // 0       1          2         3        4
    ['Date', 'Windows', 'Mac OS', 'Linux', 'Other'],
    ['7 days ago', 0, 0, 0, 0],
    ['6 days ago', 0, 0, 0, 0],
    ['5 days ago', 0, 0, 0, 0],
    ['4 days ago', 0, 0, 0, 0],
    ['3 days ago', 0, 0, 0, 0],
    ['2 days ago', 0, 0, 0, 0],
    ['1 day ago', 0, 0, 0, 0]
  ];

  $ids = array(
    "win32" => 1,
    "darwin" => 2,
    "linux" => 3
  );

  for ($i=0; $i < count($rawData); $i++) {
    $osId = identifyOS($ids, $rawData[$i]["name"]);
    $dataTable[1][$osId] += $daily ? ($rawData[$i]["7 days ago"] - $rawData[$i]["8 days ago"]) : (int)$rawData[$i]["7 days ago"];
    $dataTable[2][$osId] += $daily ? ($rawData[$i]["6 days ago"] - $rawData[$i]["7 days ago"]) : (int)$rawData[$i]["6 days ago"];
    $dataTable[3][$osId] += $daily ? ($rawData[$i]["5 days ago"] - $rawData[$i]["6 days ago"]) : (int)$rawData[$i]["5 days ago"];
    $dataTable[4][$osId] += $daily ? ($rawData[$i]["4 days ago"] - $rawData[$i]["5 days ago"]) : (int)$rawData[$i]["4 days ago"];
    $dataTable[5][$osId] += $daily ? ($rawData[$i]["3 days ago"] - $rawData[$i]["4 days ago"]) : (int)$rawData[$i]["3 days ago"];
    $dataTable[6][$osId] += $daily ? ($rawData[$i]["2 days ago"] - $rawData[$i]["3 days ago"]) : (int)$rawData[$i]["2 days ago"];
    $dataTable[7][$osId] += $daily ? ($rawData[$i]["1 day ago"] - $rawData[$i]["2 days ago"]) : (int)$rawData[$i]["1 day ago"];
  }

  return $dataTable;
}

function identifyOS($ids, $assetName) {
  foreach ($ids as $key => $id) {
    if(strpos($assetName, $key) !== false) {
      return $id;
    }
  }

  return count($ids) + 1;
}
?>
