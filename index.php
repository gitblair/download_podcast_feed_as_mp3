<?php
// get a taddy api user and api key
// create a config.php in this style:
// $taddy_user = 'xxxxx';
// $taddy_api_key = 'xxxxx';



require "config.php";

// Fetch RSS feed function
function fetch_rss_feed($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);

    $rss = curl_exec($ch);
    if ($rss === false) {
        error_log('Error fetching RSS feed: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }

    curl_close($ch);
    return $rss;
}

// Parse RSS feed function
function parse_rss_feed($rss, $limit = 3) {
    if ($rss === false) {
        return ['error' => 'Failed to fetch RSS feed.'];
    }

    $xml = @simplexml_load_string($rss);
    if ($xml === false) {
        error_log('Error parsing RSS feed.');
        return ['error' => 'Failed to parse RSS feed.'];
    }

    $channel = $xml->channel;

    $podcast_info = [
        'title' => isset($channel->title) ? (string)$channel->title : '',
        'description' => isset($channel->description) ? (string)$channel->description : '',
        'imageUrl' => isset($channel->image->url) ? (string)$channel->image->url : ''
    ];

    $episodes = [];
    $count = 0;
    foreach ($channel->item as $item) {
        if ($count >= $limit) {
            break;
        }
        $episode = [
            'title' => isset($item->title) ? (string)$item->title : '',
            'description' => isset($item->description) ? (string)$item->description : '',
            'pub_date' => isset($item->pubDate) ? (string)$item->pubDate : '',
            'audio_url' => isset($item->enclosure['url']) ? (string)$item->enclosure['url'] : ''
        ];
        $episodes[] = $episode;
        $count++;
    }

    return ['podcast_info' => $podcast_info, 'episodes' => $episodes];
}

$selected_rssUrl = $_GET['rssUrl'] ?? null;

if ($selected_rssUrl === null) {
    $podcast_info = ['title' => 'No Podcast Selected', 'description' => 'Please select a podcast to load episodes.'];
    $episodes = [];
    $latest_episodes_table = [];
} else {
    $rss_content = fetch_rss_feed($selected_rssUrl);

    if ($rss_content !== false) {
        // Fetch latest episodes (limit to 3)
        $podcast_data = parse_rss_feed($rss_content, 3);
        if (!isset($podcast_data['error'])) {
            $podcast_info = $podcast_data['podcast_info'];
            $episodes = $podcast_data['episodes'];
        } else {
            die($podcast_data['error']);
        }

        // Fetch latest episodes for table (limit to 10)
        $podcast_data_table = parse_rss_feed($rss_content, 10);
        if (!isset($podcast_data_table['error'])) {
            $latest_episodes_table = $podcast_data_table['episodes'];
        } else {
            die($podcast_data_table['error']);
        }
    } else {
        die("Error fetching RSS feed.");
    }
}

$new_podcasts = [];
if (isset($_GET['search'])) {
    $search_query = $_GET['search'];

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Podcast Downloader</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .thumbnail-img {
            max-width: 200px !important;
        }
        .progress-bar {
            transition: width 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="container mt-5">
            <h1>Podcast Downloader</h1>
            <form method="get">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" placeholder="Search for podcasts" name="search" aria-label="Search for podcasts">
                    <button class="btn btn-outline-secondary" type="submit">Search for Podcast</button>
                </div>
            </form>

            <?php if (!empty($new_podcasts)): ?>
                <h2>Search Results</h2>
                <div class="row mt-5">
                    <?php foreach ($new_podcasts as $podcast): ?>
                        <div class="col-md-2">
                            <div class="card mb-4">
                                <img src="<?php echo htmlspecialchars($podcast['imageUrl']); ?>" class="card-img-top thumbnail-img" alt="Podcast Artwork">
                            </div>
                        </div>
                        <div class="col-md-10">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($podcast['name']); ?></h5>
                                <p class="card-text overflow-auto bg-light" style="max-height: 114px;"><?php echo $podcast['description']; ?></p>
                                <a href="?rssUrl=<?php echo urlencode($podcast['rssUrl']); ?>" class="btn btn-primary">Load Podcast</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['rssUrl'])): ?>
                <div class="row mt-5">
                    <div class="col-md-2">
                        <img src="<?php echo htmlspecialchars($podcast_info['imageUrl']); ?>" alt="Podcast Artwork" class="img-fluid thumbnail-img">
                    </div>
                    <div class="col-md-10">
                        <h1><?php echo htmlspecialchars($podcast_info['title']); ?></h1>
                        <p class="card-text overflow-auto bg-light" style="max-height: 140px;"><?php echo $podcast_info['description']; ?></p>
                    </div>
                </div>

                <div id="episodes-table" class="row mt-5">
                    <h2 class="mt-5">Latest Episodes</h2>
                    <div class="table-wrapper">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="20%">Episode Title</th>
                                    <th width="40%">Description</th>
                                    <th width="20%">Published Date</th>
                                    <th width="10%">Play</th>
                                    <th width="10%">Download</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latest_episodes_table as $episode): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($episode['title']); ?></td>
                                        <td>
                                            <div class="overflow-auto bg-light" style="max-height: 50px;">
                                                <?php echo $episode['description']; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($episode['pub_date']); ?></td>
                                        <td>
                                            <?php if (!empty($episode['audio_url'])): ?>
                                                <audio controls>
                                                    <source src="<?php echo htmlspecialchars($episode['audio_url']); ?>" type="audio/mpeg">
                                                    Your browser does not support the audio element.
                                                </audio>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($episode['audio_url'])): ?>
                                                <button class="btn btn-primary download-btn" data-url="<?php echo htmlspecialchars($episode['audio_url']); ?>" data-title="<?php echo htmlspecialchars($podcast_info['title']); ?>" data-episode="<?php echo htmlspecialchars($episode['title']); ?>">Download</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="download-form" class="row mt-5" style="display:none;">
                    <div class="col-md-6">
                        <h2>Download Episode</h2>
                        <form id="downloadPodcastForm" method="post" action="download.php">
                            <div class="mb-3">
                                <label for="podcastUrl" class="form-label">Podcast URL</label>
                                <input type="text" class="form-control" id="podcastUrl" name="podcast_url" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="savePath" class="form-label">Save as</label>
                                <input type="text" class="form-control" id="savePath" name="save_path" readonly>
                            </div>
                            <button type="submit" class="btn btn-primary">Download</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.download-btn').click(function() {
                var podcastUrl = $(this).data('url');
                var podcastTitle = $(this).data('title');
                var episodeTitle = $(this).data('episode');
                var savePath = podcastTitle.replace(/[^a-z0-9]/gi, '_') + '_' + episodeTitle.replace(/[^a-z0-9]/gi, '_') + '.mp3';

                $('#podcastUrl').val(podcastUrl);
                $('#savePath').val(savePath);

                $('#episodes-table').hide();
                $('#download-form').show();
            });

            $('#downloadPodcastForm').submit(function(event) {
                event.preventDefault();

                $.ajax({
                    url: 'download.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        var res = JSON.parse(response);
                        if (res.status === 'success') {
                            alert('Download successful!');
                        } else {
                            alert('Download failed: ' + res.error);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred: ' + error);
                    }
                });
            });
        });
    </script>
</body>
</html>
