<?php
header('Content-Type: application/json');

$response = [
    'success' => false,
    'error' => ''
];

$debug = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['startTime']) || !isset($_POST['duration'])) {
        $response['error'] = 'Missing startTime or duration in POST data';
    } else {
        $startTime = intval($_POST['startTime']);
        $duration = intval($_POST['duration']);
        $podcastPath = 'test.mp3';
        $outputDir = __DIR__ . '../clips/';
        $outputFile = $outputDir . 'clip_' . time() . '.mp3';

        $debug[] = "Start Time: $startTime, Duration: $duration";
        $debug[] = "Podcast Path: $podcastPath, Output File: $outputFile";

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // Use the absolute path to ffmpeg
        $ffmpegPath = '/opt/homebrew/bin/ffmpeg';  // Change this to the output of `which ffmpeg`
        $command = "$ffmpegPath -ss $startTime -i \"$podcastPath\" -t $duration \"$outputFile\" 2>&1";
        $debug[] = "Executing command: $command";

        exec($command, $output, $return_var);

        $debug[] = "Command output: " . implode("\n", $output);
        $debug[] = "Return value: $return_var";

        if ($return_var === 0) {
            $response['success'] = true;
        } else {
            $response['error'] = 'Error executing ffmpeg command';
            $response['ffmpeg_output'] = $output;
        }

        $response['debug'] = $debug;
    }
} else {
    $response['error'] = 'Invalid request method';
}

echo json_encode($response);
?>
