var player;
var view;

window.addEventListener("load", function () {
    player = videojs('videojs-player');
    setView('live');
    setInterval(checkCameraStatus, 60000);
});

function showLiveFeed() {
    document.getElementById("other").style.display='none';
    document.getElementById("live").style.display='block';
    view = 'live';
    if (camOn) {
        camOnline();
    } else {
        camOffline();
    }
}

function showClips() {
    showOther("/clipview.php?camkey="+camKey, "clips");
}

function showImages() {
    showOther("/stillview.php?camkey="+camKey, "images");
}

function showHelp() {
    showOther("/help.html", "help");
}

function showOther(url, name) {
    player.pause();
    document.getElementById("live").style.display='none';
    view = name;
    var other = document.getElementById("other");

    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            other.innerHTML = xhttp.responseText;
        }
    }

    xhttp.open("GET", url, true);
    xhttp.send();
    other.style.display='block';
}

function camOnline() {
    document.getElementById("online").style.display='block';
    document.getElementById("offline").style.display='none';

    if (navigator.userAgent.indexOf("Windows NT 6.1")>-1 && navigator.userAgent.indexOf("rv:11.0")>-1)
    {
        var warn=document.getElementById('iewarn');
        warn.style.display="block";
        warn.innerHTML="Sorry, this webcam isn't compatible with Internet Explorer 11 on Windows 7. Please use an alternative web browser, eg <a href='http://www.getfirefox.com' target='_blank'>Firefox</a> or <a href='https://www.google.com/chrome/index.html' target='_blank'>Chrome</a>.";
    }
    if (navigator.userAgent.indexOf("MSIE")>-1) {
        var warn=document.getElementById('iewarn');
        warn.style.display="block";
        warn.innerHTML="Sorry, this webcam isn't compatible with versions of Internet Explorer less than 11. Please use an alternative web browser, eg <a href='http://www.getfirefox.com' target='_blank'>Firefox</a> or <a href='https://www.google.com/chrome/index.html' target='_blank'>Chrome</a>.";
    }
}

function camOffline() {
    player.pause();
    document.getElementById("online").style.display='none';

    var offline = document.getElementById("offlinewarn");
    if (!cameraOnline) {
        offline.innerHTML = "The network connection to the live webcam seems to have run out of steam.<br />Please come back later after we've cleaned the fire!";
    } else {
        if (maintenanceMode) {
            setMaintenanceMessage();
        } else {
            offline.innerHTML = "This webcam is now offline, operating hours are "+startTime+" to "+endTime+".<br />"+
                "Please use the links above to browse video clips and images from the past few days.";
        }
    }
    document.getElementById("offline").style.display='block';
}

function setMaintenanceMessage() {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            var offline = document.getElementById("offlinewarn");
            offline.innerHTML = xhttp.responseText;
        }
    }

    xhttp.open("GET", "/maintenance.php?camkey="+camKey, true);
    xhttp.send();
}

function checkCameraStatus() {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            var obj = JSON.parse(xhttp.responseText);

            maintenanceMode = obj.maintenanceMode;
            cameraOnline = obj.cameraOnline;

            if ( obj.cameraOn != camOn) {
                camOn = obj.cameraOn;
                if (view == 'live') {
                    showLiveFeed();
                }
            }

        }
    };
    xhttp.open("GET", "/camstatus.php?camkey="+camKey, true);
    xhttp.send();
}


function setView(view) {
    document.getElementById('content');
    if (view=='live') {
         showLiveFeed();
    }
    if (view=='clip') {
         showClips();
    }
    if (view=='image') {
         showImages();
    }
    if (view=='help') {
         showHelp();
    }

    document.getElementById("link-live").style.background="#333333";
    document.getElementById("link-clip").style.background="#333333";
    document.getElementById("link-image").style.background="#333333";
    document.getElementById("link-help").style.background="#333333";

    var ele = document.getElementById("link-"+view);
    ele.style.background="#880000";
}