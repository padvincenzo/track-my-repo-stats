<?php
require "connect.php";

function updateDB($idproject, $tag, $asset) {
  global $dbh;

  // Update the database only when "update" received
  if(!isset($_GET["db"]) || $_GET["db"] != "true") {
    return;
  }

  $filename = $asset->name;
  $downloadCount = $asset->download_count;

  $idasset = getAssetID($idproject, $tag, $filename);
  if($idasset != -1) {
    // Update the download count for this asset
    $result = $dbh->query("INSERT INTO project_downloads (idasset, dl_count) VALUES ('$idasset', '$downloadCount');");
  }
}

function getGitHubJson($url) {
  global $useragent;

  // cURL session that retrieve github stats in a json format
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $result = curl_exec($ch);
  curl_close($ch);

  return $result;
}

function analyzeRelease(&$repo, $release) {
  // Ignore releases that have no assets
  if(count($release->assets) <= 0) {
    return;
  }

  $tag = $release->tag_name;
  $repo["releases"][$tag] = [];

  foreach ($release->assets as $k => $asset) {
    $repo["releases"][$tag][$asset->name] = $asset->download_count;
    updateDB($repo["idproject"], $tag, $asset);
  }
}

function getAssetID($idproject, $tag, $filename) {
  global $dbh;

  $result = $dbh->query("SELECT idasset FROM project_asset WHERE idproject = '$idproject' AND tag = '$tag' AND filename = '$filename';");
  if($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    return $row["idasset"];
  }

  // The asset does not exists yet, add it
  $result = $dbh->query("INSERT INTO project_asset (idproject, tag, filename) VALUES ('$idproject', '$tag', '$filename');");
  if($result) {
    return $dbh->insert_id;
  }

  // Something went wrong
  return -1;
}

$result = $dbh->query("SELECT * FROM project");
if(!$result) {
  die("Error retrieving projects data");
}
$repos = $result->fetch_all(MYSQLI_ASSOC);

for($i = 0; $i < count($repos); $i++) {
  $slug = $repos[$i]["slug"];
  $repos[$i]["url"] = "https://api.github.com/repos/$username/$slug/releases";

  $result = getGitHubJson($repos[$i]["url"]);
  $releases = json_decode($result);

  // Analyze data
  $repos[$i]["releases"] = [];
  foreach ($releases as $j => $release) {
    analyzeRelease($repos[$i], $release);
  }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Repo stats</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script type="text/javascript">
      var repos = <?php echo json_encode($repos); ?>;

      window.onload = async () => {
        for(let i = 0; i < repos.length; i++) {
          printRepoStats(repos[i]);
        }
      };

      function printRepoStats(repo) {
        let total = 0;
        document.write(`<table><tr><th colspan="3">Repo: <a href="${repo.url}" target="_blank">${repo.name}</a></th></tr>`);

        for(var tag in repo.releases) {
          let subtotal = 0;

          document.write(`<tr><td rowspan="${Object.keys(repo.releases[tag]).length + 2}" style="vertical-align: top;">${repo.name} ${tag}</td><td>File</td><td>Download count</td></tr>`);
          for(var asset in repo.releases[tag]) {
            document.write(`<tr><td>${asset}</td><td>${repo.releases[tag][asset]}</td></tr>`);
            subtotal += repo.releases[tag][asset];
          }

          document.write(`<tr><td colspan="2" style="text-align: left;"><b>Subtotal: ${subtotal}</b></td></tr>`);
          total += subtotal;
        }

        document.write(`<tr><td colspan="3" style="text-align: right;"><b>Total: ${total}</b></td></tr></table>`);
      }
    </script>
  </head>
  <body>

  </body>
</html>
