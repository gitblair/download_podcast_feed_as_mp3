<?php
require "config.php";

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

$rssUrl = $_GET['rssUrl'] ?? null;

if ($rssUrl === null) {
    die(json_encode(['error' => 'No RSS URL provided.']));
}

$rss_content = fetch_rss_feed($rssUrl);

if ($rss_content !== false) {
    $podcast_data = parse_rss_feed($rss_content);
    if ($podcast_data !== false) {
        echo json_encode($podcast_data);
    } else {
        die(json_encode(['error' => 'Error parsing RSS feed.']));
    }
} else {
    die(json_encode(['error' => 'Error fetching RSS feed.']));
}
?>
