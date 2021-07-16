<?php

// Database credentials
$host = "localhost";
$user = "root";
$password = "";
$database = "my_vincenzopadula";

// Create connection
$dbh = new mysqli($host, $user, $password);

// Check connection
if ($dbh->connect_error) {
  die("Connection failed: " . $dbh->connect_error);
}

if(! $dbh->select_db($database)) {
  die("Database not found");
}

// Github username
$username = "padvincenzo";

// Choose a password
$password = "123prova";

// Check the password
$inputcode = isset($_GET["code"]) ? $_GET["code"] : "";
if($password != $inputcode) {
  die("Password missing/wrong.");
}

// Check input
$project = json_decode(file_get_contents("php://input"));
if($project && isset($_GET["update"]) && $_GET["update"] == "true") {
  foreach ($project->releases as $tag => $assets) {
    foreach ($assets as $asset => $downloadCount) {
      // Check that the asset exists
      $result = $dbh->query("select idasset from project_asset where idproject = '$project->idproject' and tag = '$tag' and filename = '$asset';");
      $idasset = 0;
      if($result->num_rows == 1) {
        $idasset = ($result->fetch_assoc())["idasset"];
      } else {
        // The asset does not exists yet, add it
        $result = $dbh->query("insert into project_asset (idproject, tag, filename) values ('$project->idproject', '$tag', '$asset');");
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

  // Stats has been updated, nothing to do anymore
  die();
}


// No input, get stored repo names
$result = $dbh->query("select * from project");
if(!$result) {
  die("Error retrieving projects data");
}

$repos = $result->fetch_all(MYSQLI_ASSOC);
for($i = 0; $i < count($repos); $i++) {
  $slug = $repos[$i]["slug"];
  $repos[$i]["url"] = "https://api.github.com/repos/$username/$slug/releases";
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
          await getRepoStats(repos[i]).then(() => {
            printRepoStats(repos[i]);
          });
        }
      };

      async function getRepoStats(repo) {
        return retrieve(repo.url).then((json) => {
          try {
            repo.releases = {};
            var releases = JSON.parse(json);

            releases.forEach((release, i) => {
              if(release.assets.length > 0) {
                repo.releases[release.tag_name] = {};

                release.assets.forEach((asset, j) => {
                  repo.releases[release.tag_name][asset.name] = asset.download_count;
                });
              }
            });

            update(repo).catch((err) => console.log(err));
          }
          catch(err) {
            document.write(err);
          }
        }).catch((err) => {
          console.log(err);
          document.write(err);
        });
      }

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

      function retrieve(url) {
        return new Promise((_resolve, _reject) => {
          var xhr = new XMLHttpRequest();

          xhr.onreadystatechange = () => {
            if (xhr.readyState == 4 && xhr.status == 200) {
              _resolve(xhr.responseText);
            }

            if(xhr.readyState == 4 && xhr.status > 299) {
              _reject(`Server Error: ${xhr.statusText}; url: ${url}`);
            }
          };

          xhr.onerror = () => {
            _reject(`xmlHTTP Error: ${xhr.responseText}`);
          };

          xhr.open("GET", url);
          xhr.send();
        });
      }

      function update(data) {
        return new Promise((_resolve, _reject) => {
          var xhr = new XMLHttpRequest();
          var json = JSON.stringify(data);

          xhr.onreadystatechange = () => {
            if (xhr.readyState == 4 && xhr.status == 200) {
              _resolve(xhr.responseText);
            }

            if(xhr.readyState == 4 && xhr.status > 299) {
              _reject(`Server Error: ${xhr.statusText}`);
            }
          };

          xhr.onerror = () => {
            _reject(`xmlHTTP Error: ${xhr.responseText}`);
          };

          xhr.open("POST", "<?php echo $_SERVER[PHP_SELF] . "?code=" . $inputcode . "&update=true"; ?>", true);
          xhr.setRequestHeader("Content-Type", "application/json");
          xhr.send(json);
        });
      }
    </script>
  </head>
  <body>

  </body>
</html>
