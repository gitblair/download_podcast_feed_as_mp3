<?php
$podcast_url = 'https://chrt.fm/track/288D49/stitcher.simplecastaudio.com/0886b221-5165-4304-b58f-c4afa4dcaf32/episodes/3fe9307e-e5de-4f10-8c91-445cc013c598/audio/128/default.mp3?aid=rss_feed&awCollectionId=0886b221-5165-4304-b58f-c4afa4dcaf32&awEpisodeId=3fe9307e-e5de-4f10-8c91-445cc013c598&feed=9cZwEL3n'; // Replace with actual URL
$save_path = 'test_podcast.mp3';

function download_podcast($url, $save_to) {
    $ch = curl_init($url);
    $fp = fopen($save_to, 'wb');

    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);

    curl_exec($ch);
    fclose($fp);
    curl_close($ch);
}

download_podcast($podcast_url, $save_path);

if (file_exists($save_path)) {
    echo 'Download successful!';
} else {
    echo 'Download failed!';
}
?>
