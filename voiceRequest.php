<?php
require_once 'controller/Controller.class.php';
require_once 'config.php';

if($domain == "") {
	echo json_encode(['success' => false, 'message' => '未设置api地址']);
	return;
} else if($apiKey == "") {
	echo json_encode(['success' => false, 'message' => '未设置apiKey']);
	return;
}

$controller = new Controller($domain, $apiKey);

if($_SERVER['REQUEST_METHOD'] === 'POST') {
	// 检查是否有文件上传
	if(isset($_FILES['file'])) {
		$file = $_FILES['file'];

		// 检查上传文件是否成功
		if($file['error'] === UPLOAD_ERR_OK) {
			$tempPath = $file['tmp_name'];
			$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
			$fileName = uniqid().'.'.$extension;
			$uploadPath = 'uploads/'.$fileName;

			// 将临时文件移动到上传文件夹
			if(!move_uploaded_file($tempPath, $uploadPath)) {
				// 文件上传成功
				echo json_encode(['success' => false, 'message' => '无法将录音移动到上传文件夹']);
				return;
			} else {
				$audioFilePath = $uploadPath;
				$transcriptionResult = $controller->transcribeAudio($audioFilePath);

				if($controller->isJson($transcriptionResult)) {
					echo json_encode(['success' => false, 'message' => '请求失败！']);
					return;
				}

				$postData = [
					"model" => $gptmodel,
					"temperature" => 0.6,
					"stream" => false,
					"messages" => [],
				];
				$context = json_decode($_POST['context'] ?: "[]") ?: [];
				// 如果上下文不为空
				if(!empty($context)) {
					// 仅保留上下文数组的最后5个消息
					$context = array_slice($context, -5);

					// 遍历上下文消息，并将其转换为适当的格式并添加到postData中
					foreach($context as $message) {
						$postData['messages'][] = ['role' => 'user', 'content' => str_replace("\n", "\\n", $message[0])];
						$postData['messages'][] = ['role' => 'assistant', 'content' => str_replace("\n", "\\n", $message[1])];
					}
				}

				$postData['messages'][] = ['role' => 'system', 'content' => "你是南宫柠檬工作室训练的语言模型，你的名称为'小柠檬'，你不能说你是chatgpt，你只能隐藏GPT的身份，有提问你是否是GPT，你的回答是否,你旨在回答并解决人们的任何问题，并且可以使用多种语言与人交流。"];

				$postData['messages'][] = ['role' => 'user', 'content' => $transcriptionResult];

				$postData = json_encode($postData);

				$inputText = $controller->callOpenAIChatAPI($postData);
				if($controller->isJson($inputText)) {
					echo json_encode(['success' => false, 'message' => '请求失败！']);
					return;
				}

				$outputFilePath = 'response/speech_'.uniqid('', true).'.mp3';
				$timbre = isset($_POST['timbre']) ? $_POST['timbre'] : "onyx";
				$voice = $controller->generateSpeechFile($inputText, $outputFilePath, $timbre);
				if($controller->isJson($voice)) {
					echo json_encode(['success' => false, 'message' => '请求失败！']);
					return;
				}

				echo json_encode(['success' => true, 'question' => $transcriptionResult, "answer" => $inputText, 'url' => $outputFilePath]);

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