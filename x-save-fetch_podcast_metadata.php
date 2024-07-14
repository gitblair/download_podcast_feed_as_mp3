<?php
require "config.php";
?>



<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// $servername = "localhost";
// $username = "pdusr";
// $password = "pdpass";
// $dbname = "podcast_directory";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Array of RSS feed URLs and corresponding names
$podcasts = [
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
  'Obscure with Michael Ian Black' => 'https://rss.art19.com/obscure',
    // Add the rest of your podcasts here
];

foreach ($podcasts as $name => $url) {
    // Check if the podcast already exists in the database and if it was updated within the last 30 days
    $stmt = $conn->prepare("SELECT last_updated FROM podcasts WHERE rss_feed_url = ?");
    $stmt->bind_param("s", $url);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($last_updated);
        $stmt->fetch();
        $stmt->close();

        $last_updated_time = strtotime($last_updated);
        $current_time = time();
        $thirty_days_ago = $current_time - (30 * 24 * 60 * 60);

        if ($last_updated_time > $thirty_days_ago) {
            continue; // Skip this podcast as it's already up-to-date
        }
    }

    // Fetch RSS feed
    $rss_content = @file_get_contents($url);
    if ($rss_content === false) {
        continue; // Skip this podcast if there's an error fetching the feed
    }

    // Parse RSS feed
    $xml = @simplexml_load_string($rss_content);
    if ($xml === false) {
        continue; // Skip this podcast if there's an error parsing the feed
    }

    $channel = $xml->channel;

    // Download and save artwork locally
    $artwork_url = (string)$channel->image->url;
    $artwork_local_filename = 'images/' . preg_replace('/[^a-zA-Z0-9-_]/', '_', $name) . '.jpg';
    file_put_contents($artwork_local_filename, file_get_contents($artwork_url));

    // Insert or update podcast metadata in the database
    $title = (string)$channel->title;
    $description = (string)$channel->description;
    $last_updated = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO podcasts (name, rss_feed_url, artwork_local_url, last_updated) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), artwork_local_url = VALUES(artwork_local_url), last_updated = VALUES(last_updated)");
    $stmt->bind_param("ssss", $title, $url, $artwork_local_filename, $last_updated);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
?>
