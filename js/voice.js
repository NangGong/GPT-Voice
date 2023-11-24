let mediaRecorder;
let audioChunks = [];
let contextarray = [];
document.getElementById("emotion").textContent = getRandomEmoji();

let timbre = document.getElementById("timbre").value;

document.getElementById("timbre").addEventListener("change", function () {
    timbre = this.value;
    console.log(timbre);
});

navigator.mediaDevices.getUserMedia({ audio: true })
    .then(stream => {
        mediaRecorder = new MediaRecorder(stream);

        mediaRecorder.ondataavailable = event => {
            if (event.data.size > 0) {
                audioChunks.push(event.data);
            }
        };

        mediaRecorder.onstop = () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
            const audioUrl = URL.createObjectURL(audioBlob);
            sendAudioToBackend(audioUrl); // 将音频URL传递给sendAudioToBackend函数
        };
    })
    .catch(error => {
        console.error('访问麦克风时出错：', error);
        layer.msg('访问麦克风时出错', { icon: 2 });
    });

document.getElementById('startRecord').addEventListener('click', () => {
    audioChunks = [];
    mediaRecorder.start();
    document.getElementById('startRecord').disabled = true;
    document.getElementById('stopRecord').disabled = false;
    document.getElementById('emotion').innerHTML = '🤔👂';
    document.getElementById('audio_rep').innerHTML = createLiElements(10);
});

document.getElementById('stopRecord').addEventListener('click', () => {
    mediaRecorder.stop();
    document.getElementById('startRecord').disabled = false;
    document.getElementById('stopRecord').disabled = true;
    document.getElementById('audio_rep').innerHTML = '';

});

function sendAudioToBackend(audioUrl) {
    const formData = new FormData();
    formData.append('file', new File(audioChunks, 'recorded_audio.wav'));
    formData.append('timbre', timbre);
    formData.append('context', JSON.stringify(contextarray));
    var loading = layer.msg('正在思考...', {
        icon: 16,
        shade: 0.4,
        time: false
    });

    fetch('request.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            layer.close(loading);
            document.getElementById("emotion").textContent = getRandomEmoji();
            document.getElementById('audio_rep').innerHTML = '';
            if (data.success == false) {
                layer.msg(data.message, { icon: 2 });
                return;
            }
            if (isWechat()) {
                const audioPlayer = document.createElement('audio');
                audioPlayer.controls = true;
                audioPlayer.src = data.url;
                document.getElementById('audio_rep').appendChild(audioPlayer);
                audioPlayer.play();
                updateContextArray(data.question, data.answer, 5);
            } else {
                try {
                    playAudioFromURL(data.url);
                    updateContextArray(data.question, data.answer, 5);
                } catch (error) {
                    const audioPlayer = document.createElement('audio');
                    audioPlayer.controls = true;
                    audioPlayer.src = data.url;
                    document.getElementById('audio_rep').appendChild(audioPlayer);
                    audioPlayer.play();
                    updateContextArray(data.question, data.answer, 5);
                }
            }
        })
        .catch(error => {
            layer.close(loading);
            console.error('将数据发送到后端时出错：', error);
            layer.msg('请求出错！', { icon: 2 });
        });
}

function playAudioFromURL(url) {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    var audioElement = new Audio(url);
    var source = audioContext.createMediaElementSource(audioElement);
    var startButton = document.getElementById('audioList');
    var stopButton = document.getElementById('audioList');

    audioElement.onplay = () => {
        audioPlaying = true; // 设置状态为正在播放
        startButton.onclick = () => {
            audioElement.pause();
            audioPlaying = false; // 设置状态为非播放
            startButton.onclick = () => {
                audioElement.play();
                audioPlaying = true; // 设置状态为正在播放
            };
        };
    };

    audioElement.onended = () => {
        audioPlaying = false; // 设置状态为非播放

        startButton.onclick = () => {
            audioElement.play();
            audioPlaying = true; // 设置状态为正在播放

        };
    };

    audioElement.onpause = () => {
        audioPlaying = false; // 设置状态为非播放

        startButton.onclick = () => {
            audioElement.play();
            audioPlaying = true; // 设置状态为正在播放

        };
    };

    source.connect(audioContext.destination);
    audioElement.play();
}

function isWechat() {
    var ua = navigator.userAgent.toLowerCase();

    var isWXWork = ua.match(/wxwork/i) == 'wxwork';

    var isWeixin = !isWXWork && ua.match(/MicroMessenger/i) == 'micromessenger';

    return isWeixin;

}
function getRandomEmoji() {
    const emojis = ["😄", "😃", "😊", "😆", "😁", "😀", "😂", "🤣", "🙂", "🙃", "😉"];
    const randomIndex = Math.floor(Math.random() * emojis.length);
    return emojis[randomIndex];
}
function updateContextArray(question, answer, maxLength) {
    contextarray.push([question, answer]);
    contextarray = contextarray.slice(-maxLength);
}
function createLiElements(num = 10) {
    var liElements = "<ul class='wave-menu'>";
    for (var i = 0; i < num; i++) { liElements += "<li></li>"; } liElements += "</ul>"; return liElements;
}
