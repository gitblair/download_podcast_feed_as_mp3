<?php
//
// Author: c. blair 2022 revised 2023, 2024.
//
//require "../config.php";

?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Blank Test</title>


    <?php //include "../includes/Bootstrap_FontAwesome.html"; ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

        <style>

        .table-wrapper {
        max-height: 600px;
        overflow: auto;
        display:inline-block;
        }
        th {
        top: 0;
        position: sticky;
        background: #e5d2f1 !important;
        color: black;
        }

        #waveform {
          width: 100%;
          height: 128px;
          background-color: #f2f2f2;
      }
    </style>


    <!-- <link rel="stylesheet" href="../styles.css"> -->

    <script>
        $(document).ready(function(){
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</head>
<body>
    <div class="wrapper">
        <div class="container-xxl">
            <div class="row">
                <div class="col-md-12">



<?php
          echo "<table class='table table-bordered table-striped'>";
          echo "<thead>";
          echo "<tr style='font-weight:400; text-align:center; background-color:gold; color:black;'>";
          echo "<th>Tester</th>";
          echo "</tr>";
          echo "<tr>";
          echo "<td style='padding-top:40px; padding-bottom:40px;'>";
          echo "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et
          dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea
          commodo consequat. Duis aute irure dolor in reprehenderit in
          voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident,
          sunt in culpa qui officia deserunt mollit anim id est laborum.</p>";
          echo "</td>";
          echo "</tr>";
          echo "<tr>";
          echo "<td style='padding-top:40px; padding-bottom:40px;'>";

?>
<script src="https://unpkg.com/wavesurfer.js@7"></script>






<script>
// const wavesurfer = WaveSurfer.create({
//   container: document.body,
//   waveColor: 'rgb(200, 0, 200)',
//   progressColor: 'rgb(100, 0, 100)',
//   url: 'test.mp3',
// })
//
// wavesurfer.on('click', () => {
//   wavesurfer.play()
// })

// All wavesurfer options in one place



const options = {
  "container": "td",
  "height": 128,
  "width": 1280,
  "splitChannels": false,
  "normalize": false,
  "waveColor": "#0b273e",
  "progressColor": "#3475cd",
  "cursorColor": "#ddd5e9",
  "cursorWidth": 2,
  "barWidth": 4,
  "barGap": 2,
  "barRadius": null,
  "barHeight": null,
  "barAlign": "center",
  "minPxPerSec": 1,
  "fillParent": true,
  "url": "clipper/test.mp3",
  "mediaControls": true,
  "autoplay": false,
  "interact": true,
  "dragToSeek": true,
  "hideScrollbar": false,
  "audioRate": 1,
  "autoScroll": true,
  "autoCenter": true,
  "sampleRate": 8000
}

const wavesurfer = WaveSurfer.create(options)


// const wavesurfer = WaveSurfer.create({
//   container: document.body,
//   waveColor: 'rgb(200, 0, 200)',
//   progressColor: 'rgb(100, 0, 100)',
//   url: 'test.mp3',
// })

wavesurfer.on('click', () => {
  wavesurfer.play()
})







</script>


<?php
echo "</td>";
echo "</tr>";
echo "<tr>";

echo "<td style='padding-top:40px; padding-bottom:40px;'>";

?>


  <!-- <video
    src="test.mov"
    controls
    playsinline
    style="width: 100%; max-width: 600px; margin: 0 auto; display: block;"
/>




<script>
const ws = WaveSurfer.create({
  container: document.body,
  height: 128,
  width: 800,
  waveColor: "#0b273e",
  progressColor: "#3475cd",
  cursorColor: "#ddd5e9",
  cursorWidth: 2,
  barWidth: 4,
  barGap: 2,
  barAlign: "center",
  // Pass the video element in the `media` param
  media: document.querySelector('video'),
})
</script> -->


<?php

echo "</td>";

echo "</tr>";
echo "</table>";
?>



                </div>
            </div>
        </div>
    </div>


</body>
</html>
