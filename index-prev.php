<?php

        // 'Content-Type: application/json',
        // "X-USER-ID: $taddy_user",
        // "X-API-KEY: $taddy_api_key",
?>
<?php
  require "config.php";
?>

<?php
// Create connection. Variables in config.php
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set the character set to utf8mb4
$conn->set_charset("utf8mb4");

function fetch_rss_feed($url) {
    $rss = @file_get_contents($url);
    if ($rss === false) {
        die("Error fetching RSS feed.");
    }
    return $rss;
}

function parse_rss_feed($rss, $limit = 3) {
    $xml = @simplexml_load_string($rss);
    if ($xml === false) {
        die("Error parsing RSS feed.");
    }

    $channel = $xml->channel;

    $podcast_info = [
        'title' => (string)$channel->title,
        'description' => (string)$channel->description,
        'imageUrl' => (string)$channel->image->url
    ];

    $episodes = [];
    $count = 0;
    foreach ($channel->item as $item) {
        if ($count >= $limit) {
            break;
        }
        $episode = [
            'title' => (string)$item->title,
            'description' => (string)$item->description,
            'pub_date' => (string)$item->pubDate,
            'audio_url' => (string)$item->enclosure['url']
        ];
        $episodes[] = $episode;
        $count++;
    }

    return ['podcast_info' => $podcast_info, 'episodes' => $episodes];
}


// Fetch podcast metadata from database
$result = $conn->query("SELECT name, itunesId, description, imageUrl, rssUrl FROM taddypodcasts");
$podcasts = [];
while ($row = $result->fetch_assoc()) {
    $podcasts[] = $row;
}


$selected_rssUrl = $_GET['rssUrl'] ?? null;

if ($selected_rssUrl === null && !empty($podcasts)) {
    $selected_rssUrl = $podcasts[0]['rssUrl'];



    // Debugging me
    //echo "<pre>";
    //var_dump($rssUrl);
    //echo "</pre>";








}

if ($selected_rssUrl === null) {
    $podcast_info = ['title' => 'No Podcast Selected', 'description' => 'Please select a podcast to load episodes.'];
    $episodes = [];
} else {
    $rss_content = fetch_rss_feed($selected_rssUrl);

    if ($rss_content !== false) {
        $podcast_data = parse_rss_feed($rss_content);
        if ($podcast_data !== false) {
            $podcast_info = $podcast_data['podcast_info'];
            $episodes = $podcast_data['episodes'];
        } else {
            die("Error parsing RSS feed.");
        }
    } else {
        die("Error fetching RSS feed.");
    }
}

$new_podcasts = [];
if (isset($_GET['search'])) {
    $search_query = $_GET['search'];

    $stmt = $conn->prepare("SELECT uuid, name, itunesId, description, imageUrl, rssUrl FROM taddypodcasts WHERE name = ?");
    $stmt->bind_param("s", $search_query);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $new_podcasts = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $api_url = 'https://api.taddy.org/graphql';
        $headers = [
            'Content-Type: application/json',
            "X-USER-ID: $taddy_user",
            "X-API-KEY: $taddy_api_key",
        ];
        $query = [
            'query' => '{
                getPodcastSeries(name: "' . $search_query . '") {
                    uuid
                    name
                    itunesId
                    description
                    imageUrl
                    rssUrl
                    itunesInfo {
                        uuid
                        baseArtworkUrlOf(size: 640)
                    }
                }
            }'
        ];

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query));
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response !== false) {
            $response_data = json_decode($response, true);
            if (isset($response_data['data']['getPodcastSeries'])) {
                $new_podcasts = $response_data['data']['getPodcastSeries'];

                if (isset($new_podcasts['uuid'])) {
                    $new_podcasts = [$new_podcasts];
                }
            } else {
                die("Error in Taddy API response.");
            }
        } else {
            die("Error searching for podcasts.");
        }
    }
    $stmt->close();
}

if (isset($_GET['rssUrl']) && !empty($_GET['rssUrl'])) {
    $rssUrl = $_GET['rssUrl'];

    $stmt = $conn->prepare("SELECT COUNT(*) FROM taddypodcasts WHERE rssUrl = ?");
    $stmt->bind_param("s", $rssUrl);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    // Debugging output for checking if podcast exists in the database
    // echo "<pre>";
    // var_dump($rssUrl, $count);
    // echo "</pre>";

    if ($count == 0) {
        $rss_content = fetch_rss_feed($rssUrl);
        if ($rss_content !== false) {
            $podcast_data = parse_rss_feed($rss_content);
            if ($podcast_data !== false) {
                $podcast_info = $podcast_data['podcast_info'];
                $uuid = ''; // Default empty value for uuid
                $itunesId = ''; // Default empty value for itunesId

                // Fetch uuid and itunesId from $new_podcasts if available
                foreach ($new_podcasts as $podcast) {
                    if ($podcast['rssUrl'] == $rssUrl) {
                        $uuid = $podcast['uuid'] ?? '';
                        $itunesId = $podcast['itunesId'] ?? '';
                        break;
                    }
                }

// Debugging output for checking parsed podcast info and fields
// echo "<pre>";
// var_dump($uuid, $itunesId, $podcast_info, $rssUrl);
// echo "</pre>";
// echo "<pre>";
// var_dump($itunesId, $podcast_info, $rssUrl);
// echo "</pre>";


                // $stmt = $conn->prepare("INSERT INTO taddypodcasts (uuid, name, itunesId, description, imageUrl, rssUrl) VALUES (?, ?, ?, ?, ?, ?)");
                // $stmt->bind_param("ssssss", $uuid, $podcast_info['title'], $itunesId, $podcast_info['description'], $podcast_info['imageUrl'], $rssUrl);
                // $stmt->execute();

                $stmt = $conn->prepare("INSERT INTO taddypodcasts (name, itunesId, description, imageUrl, rssUrl) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $podcast_info['title'], $itunesId, $podcast_info['description'], $podcast_info['imageUrl'], $rssUrl);
                $stmt->execute();


                // Debugging output for checking result of insert statement
                // echo "<pre>";
                // var_dump($stmt->error, $stmt->affected_rows);
                // echo "</pre>";

                $stmt->close();
            } else {
                die("Error parsing RSS feed.");
            }
        } else {
            die("Error fetching RSS feed.");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Podcast Directory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">


    <style>
        .thumbnail-img {
            max-width: 100px !important;
        }
    </style>



    <style>
    .progress-bar {
        transition: width 0.5s ease; /* Smooth transition for progress bar */
    }
</style>


</head>
<body>




  <div class="container-fluid">

        <?php include "nav.php"; ?>

<div class="container mt-5">

    <!-- Taddy Podcast Search Form -->
    <h1>Podcast Directory</h1>


    <code>
    to do:
    limit length of episode text with a min and max and a scrollbar for more
    remove debug
    hide the input
    rename db table
    </code>



    <form method="get">
        <div class="input-group mb-3">
            <input type="text" class="form-control" placeholder="Search for podcasts" name="search" aria-label="Search for podcasts">
            <button class="btn btn-outline-secondary" type="submit">Search</button>
        </div>
    </form>




    <!-- Podcast Artworks Grid -->
    <div class="row">
        <?php
        $count = 0;
        foreach ($podcasts as $podcast):
            ?>
            <div class="col-1 mb-2 thumbnail-row">
                <a href="?rssUrl=<?php echo urlencode($podcast['rssUrl']); ?>">
                    <img src="<?php echo htmlspecialchars($podcast['imageUrl']); ?>" alt="Podcast Artwork" class="thumbnail-img">
                </a>
            </div>
            <?php
            $count++;
            if ($count % 12 == 0) {
                echo '</div><div class="row mb-1 thumbnail-row">';
            }
        endforeach;
        ?>
    </div>



    <!-- Taddy Podcast Search Results -->
    <?php if (!empty($new_podcasts)): ?>
        <h2>Search Results</h2>
        <div class="row">
            <?php foreach ($new_podcasts as $podcast): ?>
                <div class="col-md-4">
                    <div class="card mb-4">
                        <img src="<?php echo htmlspecialchars($podcast['imageUrl']); ?>" class="card-img-top" alt="Podcast Artwork">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($podcast['name']); ?></h5>
                            <p class="card-text"><?php echo $podcast['description']; ?></p>
                            <a href="?rssUrl=<?php echo urlencode($podcast['rssUrl']); ?>" class="btn btn-primary">Load Podcast</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>



    <?php endif; ?>








        <!-- Podcast Selected from Podcast Grid or Loaded from Search Results-->


<!--   Debugging TESTERS -->
<?php //if (!isset($search_query)): ?>
<?php //echo "search not set<BR />";?>
<?php //endif; ?>

<?php //if (!isset($_GET['rssUrl'])): ?>
<?php //echo "rssurl not set <BR />"; ?>
<?php //endif; ?>

<?php //if (!isset($search_query) && !isset($_GET['rssUrl'])): ?>
<?php //echo "both<BR />";?>
<?php //endif; ?>


<?php //if (isset($search_query)): ?>
<?php //exit("search is set. shut up"); ?>
<?php //endif; ?>




          <?php if (isset($_GET['rssUrl'])): ?>


    <div class="row mt-5">
        <div class="col-md-4">
            <img src="<?php echo htmlspecialchars($podcast_info['imageUrl']); ?>" alt="Podcast Artwork" class="img-fluid" style="max-width: 400px;">
        </div>
        <div class="col-md-8">
            <h1><?php echo htmlspecialchars($podcast_info['title']); ?></h1>
            <p><?php echo $podcast_info['description']; ?></p>
        </div>
    </div>

    <hr>

    <h2 class="mt-5">Latest Episodes</h2>
    <div class="row">
        <?php foreach ($episodes as $episode): ?>
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($episode['title']); ?></h5>
                        <p class="card-text"><?php echo $episode['description']; ?></p>
                        <p class="card-text"><small class="text-muted"><?php echo htmlspecialchars($episode['pub_date']); ?></small></p>
                        <audio controls>
                            <source src="<?php echo htmlspecialchars($episode['audio_url']); ?>" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>




                    <!-- <div class="container mt-5">
                            <form id="downloadForm" action="download.php" method="post">
                                <div class="mb-3">
                                    <label for="podcast_url" class="form-label">Podcast URL:</label>
                                    <input type="text" id="podcast_url" name="podcast_url" class="form-control" value="<?php echo htmlspecialchars($episode['audio_url']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="save_path" class="form-label">Save Path:</label>
                                    <input type="text" id="save_path" name="save_path" class="form-control" value="clips/toriesout.mp3" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Download Podcast</button>
                            </form>
                            <div class="progress mt-3" style="height: 30px;">
                                <div id="progress-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                        </div>

                    <script>
          document.getElementById('downloadForm').addEventListener('submit', function(event) {
              event.preventDefault();
              const form = event.target;
              const formData = new FormData(form);
              const xhr = new XMLHttpRequest();

              xhr.open('POST', form.action, true);

              xhr.responseType = 'blob'; // Set response type to 'blob' to handle binary data

              xhr.onprogress = function(event) {
                  if (event.lengthComputable) {
                      const percentComplete = (event.loaded / event.total) * 100;
                      const progressBar = document.getElementById('progress-bar');
                      progressBar.style.width = percentComplete + '%';
                      progressBar.setAttribute('aria-valuenow', percentComplete);
                      progressBar.textContent = Math.round(percentComplete) + '%';
                  }
              };

              xhr.onload = function() {
                  if (xhr.status === 200) {
                      const blob = new Blob([xhr.response], { type: 'application/octet-stream' });
                      const url = window.URL.createObjectURL(blob);
                      const a = document.createElement('a');
                      a.style.display = 'none';
                      a.href = url;
                      a.download = formData.get('save_path');
                      document.body.appendChild(a);
                      a.click();
                      window.URL.revokeObjectURL(url);

                      const progressBar = document.getElementById('progress-bar');
                      progressBar.style.width = '100%';
                      progressBar.setAttribute('aria-valuenow', 100);
                      progressBar.textContent = '100%';

                      // Slight delay to ensure smooth animation for small files
                      setTimeout(() => {
                          progressBar.style.width = '0%';
                          progressBar.setAttribute('aria-valuenow', 0);
                          progressBar.textContent = '0%';
                      }, 500);
                  } else {
                      alert('An error occurred during the download in setTimeout function');
                  }
              };

              xhr.onerror = function() {
                  alert('An error occurred during the download.');
              };

              xhr.send(formData);
          });
      </script> -->




      <div class="container mt-5">
       <form id="downloadForm">
           <div class="mb-3">
               <label for="podcast_url" class="form-label">Podcast URL:</label>
               <input type="text" id="podcast_url" name="podcast_url" class="form-control" value="<?php echo htmlspecialchars($episode['audio_url']); ?>" required>
           </div>
           <div class="mb-3">
               <label for="save_path" class="form-label">Save Path:</label>
               <input type="text" id="save_path" name="save_path" class="form-control" value="clips/testdown.mp3" required>
           </div>
           <button type="submit" class="btn btn-primary">Download Podcast</button>
       </form>
       <div class="progress mt-3" style="height: 30px;">
           <div id="progress-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
       </div>
   </div>

   <script>
       document.getElementById('downloadForm').addEventListener('submit', function(event) {
           event.preventDefault();
           const form = event.target;
           const formData = new FormData(form);
           const xhr = new XMLHttpRequest();

           xhr.open('POST', 'download.php', true);

           xhr.onreadystatechange = function() {
               if (xhr.readyState === XMLHttpRequest.DONE) {
                   if (xhr.status === 200) {
                       const response = JSON.parse(xhr.responseText);
                       if (response.status === 'success') {
                           alert('Download complete!');
                       } else {
                           alert('An error occurred during the download.');
                       }
                   }
               }
           };

           xhr.upload.onprogress = function(event) {
               if (event.lengthComputable) {
                   const percentComplete = (event.loaded / event.total) * 100;
                   const progressBar = document.getElementById('progress-bar');
                   progressBar.style.width = percentComplete + '%';
                   progressBar.setAttribute('aria-valuenow', percentComplete);
                   progressBar.textContent = Math.round(percentComplete) + '%';
               }
           };

           xhr.send(formData);

           // Simulate progress for small files
           const progressBar = document.getElementById('progress-bar');
           let simulatedProgress = 0;
           const interval = setInterval(() => {
               simulatedProgress += 10;
               progressBar.style.width = simulatedProgress + '%';
               progressBar.setAttribute('aria-valuenow', simulatedProgress);
               progressBar.textContent = simulatedProgress + '%';
               if (simulatedProgress >= 100) {
                   clearInterval(interval);
               }
           }, 100);
       });
   </script>



                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

  <?php endif; ?>












</div>



<!-- end container-fluid -->

</div>


</body>
</html>
