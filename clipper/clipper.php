<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MP3 Audio Editor</title>

    <style>
            .audio-container {
                position: relative;
                width: 100%;
            }
            #waveform {
                width: 100%;
                height: 128px;
            }
        </style>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/3.3.3/wavesurfer.min.css" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body>

  <div class="container-fluid">

        <?php include "../nav.php"; ?>

<div class="container mt-5">


  <h1>MP3 Clipper</h1>

<code>
to do:
also download clip
remove debug
input selection of source
</code>


    <div class="audio-container">
           <div id="waveform"></div>
       </div>
       <br>
       <!-- <button onclick="submitClip()">Submit Clip</button> -->


<button class="btn btn-outline-secondary" onclick="submitClip()">Clip it</button>

       <div id="debug"></div>

       <script src="https://cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/3.3.3/wavesurfer.min.js"></script>
       <script src="https://cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/3.3.3/plugin/wavesurfer.regions.min.js"></script>
       <script>
           const waveformContainer = document.getElementById('waveform');

           let startTime = 0;
           let endTime = 0;

           const wavesurfer = WaveSurfer.create({
               container: waveformContainer,
               waveColor: 'violet',
               progressColor: 'purple',
               cursorColor: 'navy',
               height: 128,
               normalize: true,
               plugins: [
                   WaveSurfer.regions.create()
               ]
           });

           wavesurfer.load('test.mp3');

           wavesurfer.on('ready', () => {
               const duration = wavesurfer.getDuration();
               startTime = (duration / 2) - 10;
               endTime = startTime + 10;

               wavesurfer.addRegion({
                   start: startTime,
                   end: endTime,
                   color: 'rgba(0, 255, 0, 0.1)',
                   drag: true,
                   resize: true
               });
           });

           wavesurfer.on('region-updated', (region) => {
               startTime = region.start;
               endTime = region.end;
           });

           function submitClip() {
               const duration = endTime - startTime;

               if (duration <= 0) {
                   alert('End time must be greater than start time');
                   return;
               }

               const formData = new FormData();
               formData.append('startTime', Math.floor(startTime));
               formData.append('duration', Math.floor(duration));

               console.log("FormData being sent: Start Time - " + startTime + ", Duration - " + duration);

               fetch('clipperbackend.php', {
                   method: 'POST',
                   body: formData
               })
               .then(response => {
                   if (!response.ok) {
                       throw new Error('Network response was not ok');
                   }
                   return response.json();
               })
               .then(data => {
                   console.log("Response data:", data);
                   document.getElementById('debug').innerText = JSON.stringify(data, null, 2);
                   if (data.success) {
                       alert('Clip saved successfully!');
                   } else {
                       alert('Error saving clip: ' + (data.error || 'Unknown error'));
                   }
               })
               .catch(error => {
                   console.error('Fetch error:', error);
                   alert('Error saving clip: ' + error.message);
               });
           }

           // Event listener for spacebar to play/pause audio from the current cursor position
           document.addEventListener('keydown', (event) => {
               if (event.code === 'Space') {
                   event.preventDefault(); // Prevent default spacebar action
                   if (wavesurfer.isPlaying()) {
                       wavesurfer.pause();
                   } else {
                       wavesurfer.play();
                   }
               }
           });
       </script>



</div>
<!-- end container-fluid -->
</div>
</body>
</html>
