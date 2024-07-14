<?php
// Create connection. Variables in config.php
include 'config.php'; // Ensure this file contains your DB credentials and $taddy_user, $taddy_api_key

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set the character set to utf8mb4
$conn->set_charset("utf8mb4");

// Ensure the script outputs JSON
header('Content-Type: application/json');

$rssUrl = $_POST['rssUrl'] ?? null;

if ($rssUrl === null) {
    echo json_encode(['error' => 'RSS URL is required']);
    exit;
}

function fetch_rss_feed($url) {
    $rss = @file_get_contents($url);
    if ($rss === false) {
        return false;
    }
    return $rss;
}

function parse_rss_feed($rss, $limit = 3) {
    $xml = @simplexml_load_string($rss);
    if ($xml === false) {
        return false;
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

$stmt = $conn->prepare("SELECT COUNT(*) FROM taddypodcasts WHERE rssUrl = ?");
$stmt->bind_param("s", $rssUrl);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count == 0) {
    $rss_content = fetch_rss_feed($rssUrl);
    if ($rss_content !== false) {
        $podcast_data = parse_rss_feed($rss_content);
        if ($podcast_data !== false) {
            $podcast_info = $podcast_data['podcast_info'];
            $uuid = ''; // Default empty value for uuid
            $itunesId = ''; // Default empty value for itunesId

            // Fetch uuid and itunesId from $new_podcasts if available
            $stmt = $conn->prepare("SELECT uuid, name, itunesId, description, imageUrl, rssUrl FROM taddypodcasts WHERE rssUrl = ?");
            $stmt->bind_param("s", $rssUrl);
            $stmt->execute();
            $result = $stmt->get_result();
            $new_podcasts = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($new_podcasts as $podcast) {
                if ($podcast['rssUrl'] == $rssUrl) {
                    $uuid = $podcast['uuid'] ?? '';
                    $itunesId = $podcast['itunesId'] ?? '';
                    break;
                }
            }

            $stmt = $conn->prepare("INSERT INTO taddypodcasts (uuid, name, itunesId, description, imageUrl, rssUrl) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $uuid, $podcast_info['title'], $itunesId, $podcast_info['description'], $podcast_info['imageUrl'], $rssUrl);
            $stmt->execute();
            $stmt->close();
        } else {
            echo json_encode(['error' => 'Error parsing RSS feed']);
            exit;
        }
    } else {
        echo json_encode(['error' => 'Error fetching RSS feed']);
        exit;
    }
}

$result = $conn->query("SELECT uuid, name, itunesId, description, imageUrl, rssUrl FROM taddypodcasts");
$podcasts = [];
while ($row = $result->fetch_assoc()) {
    $podcasts[] = $row;
}

echo json_encode($podcasts);
?>
