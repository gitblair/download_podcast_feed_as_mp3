<?php
function fetch_rss_feed($url) {
    $rss = @file_get_contents($url);
    if ($rss === false) {
        return false;
    }
    return $rss;
}

function parse_rss_feed($rss, $limit = 3) {
    $xml = simplexml_load_string($rss);
    if ($xml === false) {
        return false;
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
            'audio_url' => (string)$item->enclosure['url'],
        ];
        $episodes[] = $episode;
        $count++;
    }

    return ['podcast_info' => $podcast_info, 'episodes' => $episodes];
}

// Array of RSS feed URLs
$rss_feed_urls = [
    'Comedy Bang Bang' => 'https://feeds.simplecast.com/byb4nhvN',
    'Chinwag' => 'https://feeds.megaphone.fm/chinwag',
    'The Rest is Entertainment' => 'https://feeds.megaphone.fm/GLT2052042801',
    'Richard Herring\'s RHLSTP' => 'http://rss.acast.com/rhlstp',
    'Second in Command VEEP Rewatch' => 'https://www.omnycontent.com/d/playlist/885ace83-027a-47ad-ad67-aca7002f1df8/4fd831e0-f4a1-4b51-9fc6-aefa015b0fc2/5c147b1c-cade-4df1-9f79-aefa015b0fe3/podcast.rss',
    'Off Menu with Ed Gamble and James Acaster' => 'https://rss.acast.com/offmenu',
    'The Dollop with Dave Anthony and Gareth Reynolds' => 'https://www.omnycontent.com/d/playlist/885ace83-027a-47ad-ad67-aca7002f1df8/22b063ac-654d-428f-bd69-ae2400349cde/65ff0206-b585-4e2a-9872-ae240034c9c9/podcast.rss',
    'Taskmaster Ed Gamble' => 'https://feeds.captivate.fm/taskmaster-the-podcast/',
    'Taskmaster The People\'s Podcast' => 'https://feeds.captivate.fm/taskmaster-the-peoples-podcast/',
    'James O\'Brien The Whole Show' => 'https://feeds.captivate.fm/james-obrien-the-who/',
    'Pod Save The UK with Nish Kumar and Coco Khan' => 'https://feeds.simplecast.com/snMMEVFU',
    'WTF with Marc Maron Podcast' => 'https://feeds.acast.com/public/shows/62a222737c02140013aa4c03',
    'The Psychologists Are In with Maggie Lawson and Timothy Omundson' => 'https://feeds.megaphone.fm/HSW9029785234',
    'We\'re Here to Help - Jake Johnson and Gareth Reynolds' => 'https://rss.art19.com/were-here-to-help',
    'The News Agents' => 'https://feeds.captivate.fm/the-news-agents/',
    'Obscure with Michael Ian Black' => 'https://rss.art19.com/obscure'
];

$selected_rss_feed_url = $_GET['rss_feed_url'] ?? $rss_feed_urls['Comedy Bang Bang'];
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Podcast Directory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">

    <!-- Podcast Artworks Grid -->
    <div class="row mb-5">
        <?php
        $count = 0;
        foreach ($rss_feed_urls as $name => $url):
            $temp_rss_content = fetch_rss_feed($url);
            if ($temp_rss_content !== false) {
                $temp_podcast_data = parse_rss_feed($temp_rss_content, 1);
                if ($temp_podcast_data !== false) {
                    $artwork_url = htmlspecialchars($temp_podcast_data['podcast_info']['artwork_url']);
                    ?>
                    <div class="col-1 mb-3">
                        <a href="?rss_feed_url=<?php echo urlencode($url); ?>">
                            <img src="<?php echo $artwork_url; ?>" alt="Podcast Artwork" class="img-fluid" style="max-width: 100px;">
                        </a>
                    </div>
                    <?php
                    $count++;
                    if ($count == 10) {
                        echo '</div><div class="row mb-5">';
                    }
                }
            }
        endforeach;
        ?>
    </div>

    <form method="post" class="mb-5">
        <div class="input-group">
            <label class="input-group-text" for="rssFeedSelect">Select Podcast</label>
            <select class="form-select" id="rssFeedSelect" name="rss_feed_url">
                <?php foreach ($rss_feed_urls as $name => $url): ?>
                    <option value="<?php echo htmlspecialchars($url); ?>" <?php echo $url === $selected_rss_feed_url ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($name); ?>
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
