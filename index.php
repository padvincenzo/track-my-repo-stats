<?php
require "connect.php";

$week = getWeek();
$weekTable = json_encode(getTable($week, true, true));

$weekByOSTable = json_encode(getWeekByOS());

$totals = getTotals();
$totalsTable = json_encode(getTable($totals, false, false));

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Repo stats</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawCharts);

      function drawCharts() {
        var week = google.visualization.arrayToDataTable(<?php echo $weekTable; ?>);
        var totals = google.visualization.arrayToDataTable(<?php echo $totalsTable; ?>);
        var byOS = google.visualization.arrayToDataTable(<?php echo $weekByOSTable; ?>);

        var assetsChart = new google.visualization.LineChart(document.getElementById('assets_downloads'));
        assetsChart.draw(week, {
          title: 'Assets downloads',
          legend: {
            position: 'bottom',
            alignment: 'start'
          }
        });

        var projectsChart = new google.visualization.LineChart(document.getElementById('projects_downloads'));
        projectsChart.draw(totals, {
          title: 'Projects downloads',
          legend: {
            position: 'right'
          }
        });

        var byOSChart = new google.visualization.LineChart(document.getElementById('os_downloads'));
        byOSChart.draw(byOS, {
          title: 'OS downloads',
          legend: {
            position: 'right'
          }
        });
      }
    </script>
  </head>
  <body>

    <h2>Your repositories stats</h2>

    <div id="assets_downloads"></div>
    <br>
    <div id="projects_downloads"></div>
    <br>
    <div id="os_downloads"></div>

  </body>
</html>
