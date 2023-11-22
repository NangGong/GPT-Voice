<?php
require_once 'controller/Controller.class.php';
//session_start(); // 启动会话
$domain = ''; //域名 如https://api.openai.com
$apiKey = ''; //apikey 如sk-xxxxx

$controller = new Controller($domain, $apiKey);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查是否有文件上传
    if (isset($_FILES['file'])) {
        $file = $_FILES['file'];

        // 检查上传文件是否成功
        if ($file['error'] === UPLOAD_ERR_OK) {
            $tempPath = $file['tmp_name'];
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $extension;
            $uploadPath = 'uploads/' . $fileName;

            // 将临时文件移动到上传文件夹
            if (!move_uploaded_file($tempPath, $uploadPath)) {
                // 文件上传成功
                echo json_encode(['success' => false, 'message' => '无法将录音移动到上传文件夹']);
                return;
            } else {

                $audioFilePath = $uploadPath;
                $transcriptionResult = $controller->transcribeAudio($audioFilePath);

                $postData = [
                    "model" => "gpt-3.5-turbo-1106",
                    "temperature" => 0.6,
                    "stream" => false,
                    "messages" => [],
                ];

                if (!empty($_SESSION['history'])) {
                    // 仅保留上下文数组的最后5个消息
                    $context = array_slice($_SESSION['history'], -5);

                    // 遍历上下文消息，并将其转换为适当的格式并添加到postData中
                    foreach ($context as $message) {
                        $postData['messages'][] = ['role' => 'user', 'content' => str_replace("\n", "\\n", $message[0])];
                        $postData['messages'][] = ['role' => 'assistant', 'content' => str_replace("\n", "\\n", $message[1])];
                    }
                }


                $postData['messages'][] = ['role' => 'user', 'content' => $transcriptionResult];

                $context[] = $transcriptionResult;


                $postData = json_encode($postData);
                $inputText = $controller->callOpenAIChatAPI($postData);
                $context[] = $inputText;
                $outputFilePath = 'yy/speech_' . uniqid('', true) . '.mp3';
                $controller->generateSpeechFile($inputText, $outputFilePath);
                echo json_encode(['success' => true, 'message' => $outputFilePath]);

                $_SESSION['history'] = $context;
            }
        } else {
            // 文件上传失败
            echo json_encode(['success' => false, 'message' => '录音上传失败']);
        }
    } else {
        // 没有文件上传
        echo json_encode(['success' => false, 'message' => '未选择录音文件']);
    }
} else {
    // 非POST请求
    echo json_encode(['success' => false, 'message' => '无效的请求']);
}

