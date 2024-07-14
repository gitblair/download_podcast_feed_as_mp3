<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MP3 Audio Editor</title>


  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">



</head>
<body>





  <div class="container-fluid">

<div class="container mt-5">
  <ul class="nav justify-content-end">
    <li class="nav-item">
      <a class="nav-link active" aria-current="page" href="#">Active</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="http://localhost:8888/podcasthost/v5-addingmp3download.php">v5-addingmp3download.php</a>
    </li>


    <li class="nav-item">
      <a class="nav-link" href="http://localhost:8888/podcasthost/test_download.php">test_download.php</a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="http://localhost:8888/podcasthost/clipper/clipper.php">clipper.php</a>
    </li>
</div>


<div class="container mt-5">




  <!-- Clipper Form -->
  <div class="row">







    <audio id="podcast" controls>
        <source src="test.mp3" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>
    <br>
    Start Time (seconds): <input type="number" id="startTime" min="0" step="1">
    <br>
    End Time (seconds): <input type="number" id="endTime" min="0" step="1">
    <br>
    <button onclick="submitClip()">Submit Clip</button>
    <div id="debug"></div>

    <script>
        function submitClip() {
            const startTime = parseInt(document.getElementById('startTime').value);
            const endTime = parseInt(document.getElementById('endTime').value);
            const duration = endTime - startTime;

            if (duration <= 0) {
                alert('End time must be greater than start time');
                return;
            }

            const formData = new FormData();
            formData.append('startTime', startTime);
            formData.append('duration', duration);

            console.log("FormData being sent: Start Time - " + startTime + ", Duration - " + duration);

            fetch('clipbackend.php', {
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
    </script>

  </div>


</div>

<!-- end container-fluid -->

</div>


</body>
</html>
