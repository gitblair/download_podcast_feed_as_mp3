<?php
require "config.php";
?>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<?php
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
        'artwork_url' => (string)$channel->image->url
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

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch podcast metadata from database
$result = $conn->query("SELECT name, rss_feed_url, artwork_local_url FROM podcasts");
$podcasts = [];
while ($row = $result->fetch_assoc()) {
    $podcasts[] = $row;
}

$selected_rss_feed_url = $_GET['rss_feed_url'] ?? $podcasts[0]['rss_feed_url'];
$rss_content = fetch_rss_feed($selected_rss_feed_url);
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

$new_podcasts = [];
if (isset($_GET['search'])) {
    $search_query = $_GET['search'];

    $api_url = 'https://api.taddy.org/graphql';
    $headers = [
        'Content-Type: application/json',
        $taddy_user,
        $taddy_api_key,
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

            // Ensure $new_podcasts is an array of arrays
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
</head>
<body>


<div class="container mt-5">


    <!-- Taddy Form -->
  <div class="row mb-5">
  <form method="get" class="mt-4">
      <div class="input-group">
          <input type="text" name="search" class="form-control" placeholder="Search for a podcast" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
          <button class="btn btn-primary" type="submit">Search</button>
      </div>
  </form>
</div>

  <!-- Taddy Form Results -->

  <?php if (!empty($new_podcasts)): ?>
      <h2>Search Results</h2>
      <div class="row">
          <?php foreach ($new_podcasts as $podcast): ?>
              <div class="col-md-4">
                  <div class="card mb-4">
                      <img src="<?php echo htmlspecialchars($podcast['itunesInfo']['baseArtworkUrlOf'] ?? $podcast['imageUrl']); ?>" class="card-img-top" alt="Podcast Artwork">
                      <div class="card-body">
                          <h5 class="card-title"><?php echo htmlspecialchars($podcast['name']); ?></h5>
                          <p class="card-text"><?php echo $podcast['description']; ?></p>
                          <a href="?rss_feed_url=<?php echo urlencode($podcast['rssUrl']); ?>" class="btn btn-primary">Load Podcast</a>
                      </div>
                  </div>
              </div>
          <?php endforeach; ?>
      </div>
  <?php endif; ?>


    <!-- Podcast Artworks Grid -->
    <div class="row">
        <?php
        $count = 0;
        foreach ($podcasts as $podcast):
            ?>
            <div class="col-1 mb-2 thumbnail-row">
                <a href="?rss_feed_url=<?php echo urlencode($podcast['rss_feed_url']); ?>">
                    <img src="<?php echo htmlspecialchars($podcast['artwork_local_url']); ?>" alt="Podcast Artwork" class="thumbnail-img">
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



    <div class="row mt-5">
        <div class="col-md-4">
            <img src="<?php echo htmlspecialchars($podcast_info['artwork_url']); ?>" alt="Podcast Artwork" class="img-fluid" style="max-width: 400px;">
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
                        <audio controls>
                            <source src="<?php echo htmlspecialchars($episode['audio_url']); ?>" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>



</body>
</html>
