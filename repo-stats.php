<?php

/****** Credentials ******/

// Database
$host = "localhost";
$user = "root";
$password = "";
$database = "";

// Github username
$username = "";

// Choose a password (empty string = no password)
$password = "";

// Set your user-agent
$useragent = "";


/****** Code ******/

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


  $result = $dbh->query("select * from project");
  if(!$result) {
    die("Error retrieving projects data");
  }

$repos = $result->fetch_all(MYSQLI_ASSOC);

for($i = 0; $i < count($repos); $i++) {
  $slug = $repos[$i]["slug"];
  $repos[$i]["url"] = "https://api.github.com/repos/$username/$slug/releases";

  // cURL session that retrieve github stats in a json format
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $repos[$i]["url"]);
  curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $result = curl_exec($ch);
  curl_close($ch);

  // Decode the json string
  $releases = json_decode($result);

  // Analyze data
  $repos[$i]["releases"] = [];
  foreach ($releases as $j => $release) {

    // Ignore releases that have no assets
    if(count($release->assets) > 0) {
      $tag = $release->tag_name;
      $repos[$i]["releases"][$tag] = array();
      foreach ($release->assets as $k => $asset) {
        $repos[$i]["releases"][$tag][$asset->name] = $asset->download_count;

        // Update the database only when "update" received
        if(isset($_GET["update"]) && $_GET["update"] == "true") {
          $idproject = $repos[$i]["idproject"];
          $filename = $asset->name;
          $downloadCount = $asset->download_count;

          $result = $dbh->query("select idasset from project_asset where idproject = '$idproject' and tag = '$tag' and filename = '$filename';");
          $idasset = 0;
          if($result->num_rows == 1) {
            $idasset = ($result->fetch_assoc())["idasset"];
          } else {
            // The asset does not exists yet, add it
            $result = $dbh->query("insert into project_asset (idproject, tag, filename) values ('$idproject', '$tag', '$filename');");
            if($result) {
              $idasset = $dbh->insert_id;
            }
          }
          if($idasset != 0) {
            // Update the download count for this asset
            $result = $dbh->query("insert into project_downloads (idasset, dl_count) values ('$idasset', '$downloadCount');");
          }
        }
      }
    }
  }
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Repo stats</title>

    <script>
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
