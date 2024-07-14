<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $podcast_url = filter_var($_POST['podcast_url'], FILTER_SANITIZE_URL);
    $save_path = filter_var($_POST['save_path'], FILTER_SANITIZE_STRING);

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

    // Ensure the directory for saving the file exists
    if (!file_exists(dirname($save_path))) {
        mkdir(dirname($save_path), 0777, true);
    }

    // Download the podcast
    download_podcast($podcast_url, $save_path);

    // Notify the client that the download is complete
    echo json_encode(['status' => 'success']);
    exit;
} else {
    echo json_encode(['error' => 'Invalid request method!']);
}
?>












<?php
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $podcast_url = filter_var($_POST['podcast_url'], FILTER_SANITIZE_URL);
//     $save_path = filter_var($_POST['save_path'], FILTER_SANITIZE_STRING);
//
//     function download_podcast($url, $save_to) {
//         $ch = curl_init($url);
//         $fp = fopen($save_to, 'wb');
//
//         curl_setopt($ch, CURLOPT_FILE, $fp);
//         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//         curl_setopt($ch, CURLOPT_FAILONERROR, true);
//
//         curl_exec($ch);
//         fclose($fp);
//         curl_close($ch);
//     }
//
//     // Ensure the directory for saving the file exists
//     if (!file_exists(dirname($save_path))) {
//         mkdir(dirname($save_path), 0777, true);
//     }
//
//     // Download the podcast
//     download_podcast($podcast_url, $save_path);
//
//     // Set headers to prompt download
//     header('Content-Description: File Transfer');
//     header('Content-Type: application/octet-stream');
//     header('Content-Disposition: attachment; filename="' . basename($save_path) . '"');
//     header('Expires: 0');
//     header('Cache-Control: must-revalidate');
//     header('Pragma: public');
//     header('Content-Length: ' . filesize($save_path));
//     readfile($save_path);
//     exit;
// } else {
//     echo json_encode(['error' => 'Invalid request method!']);
// }
?>
