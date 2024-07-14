<?php

//this version is to use a listennotes api to search for feeds


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

$servername = "localhost";
$username = "pdusr";
$password = "pdpass";
$dbname = "podcast_directory";

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

// Search for new podcasts using Listen Notes API
$new_podcasts = [];
if (!empty($_GET['search_query'])) {
    $api_key = '5b7b2d22eaa84ca4a1396e2a0148ff8b';
    $search_query = urlencode($_GET['search_query']);
    $api_url = "https://listen-api.listennotes.com/api/v2/search?q=$search_query";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-ListenAPI-Key: $api_key"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response !== false) {
        $response_data = json_decode($response, true);
        $new_podcasts = $response_data['results'];
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
    <!-- Search for new podcasts -->
    <form method="get" class="mb-5">
        <div class="input-group">
            <input type="text" class="form-control" name="search_query" placeholder="Search for podcasts...">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>

    <!-- Display search results -->
    <?php if (!empty($new_podcasts)): ?>
        <h2>Search Results</h2>
        <div class="row">
            <?php foreach ($new_podcasts as $podcast): ?>
                <div class="col-md-4">
                    <div class="card mb-4">
                        <img src="<?php echo htmlspecialchars($podcast['image']); ?>" class="card-img-top" alt="Podcast Artwork">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($podcast['title_original']); ?></h5>
                            <p class="card-text"><?php echo $podcast['description_original']; ?></p>
                            <a href="?rss_feed_url=<?php echo urlencode($podcast['rss']); ?>" class="btn btn-primary">Load Podcast</a>
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

    <form method="get" class="mb-5">
        <div class="input-group">
            <label class="input-group-text" for="rssFeedSelect">Select Podcast</label>
            <select class="form-select" id="rssFeedSelect" name="rss_feed_url">
                <?php foreach ($podcasts as $podcast): ?>
                    <option value="<?php echo htmlspecialchars($podcast['rss_feed_url']); ?>" <?php echo $podcast['rss_feed_url'] === $selected_rss_feed_url ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($podcast['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit">Load Podcast</button>
        </div>
    </form>

    <div class="row">
        <div class="col-md-4">
            <img src="<?php echo htmlspecialchars($podcast_info['artwork_url']); ?>" alt="Podcast Artwork" class="img-fluid" style="max-width: 400px;">
        </div>
        <div class="col-md-8">
            <h1><?php echo htmlspecialchars($podcast_info['title']); ?></h1>
            <p><?php echo $podcast_info['description']; ?></p>
        </div>
    </div>

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

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
